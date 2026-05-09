// ═══════════════════════════════════════════════════════════════════════════
//  face_logic.js  —  Enregistrement du visage (enrollment)
//  Corrections : modèle cohérent, attente vidéo prête, seuil abaissé,
//                boucle robuste, erreurs visibles
// ═══════════════════════════════════════════════════════════════════════════

const MODEL_URL = '../../models'; // Chemin vers vos fichiers .bin/.json

async function startFaceEnroll() {
    const status = document.getElementById('enroll-status');
    const video  = document.getElementById('video');

    // ── 1. Charger les modèles ────────────────────────────────────────────
    try {
        status.innerHTML = "⏳ Chargement des modèles IA...";

        // On utilise TinyFaceDetector partout (léger, rapide, cohérent)
        await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
        await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
        await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);

        status.innerHTML = "✅ Modèles chargés. Démarrage caméra...";
    } catch (err) {
        status.innerHTML = "<span style='color:red;'>❌ Erreur chargement modèles : " + err.message + "</span>";
        console.error("Erreur chargement modèles face-api :", err);
        return;
    }

    // ── 2. Démarrer la caméra ─────────────────────────────────────────────
    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: 640, height: 480 }
        });
        video.srcObject = stream;
    } catch (err) {
        status.innerHTML = "<span style='color:red;'>❌ Caméra non disponible : " + err.message + "</span>";
        console.error("Erreur caméra :", err);
        return;
    }

    // ── 3. Attendre que la vidéo soit vraiment prête ──────────────────────
    await new Promise(resolve => {
        if (video.readyState >= 2) { resolve(); return; }
        video.addEventListener('loadeddata', resolve, { once: true });
    });

    status.innerHTML = "👤 Positionnez votre visage face à la caméra...";

    // ── 4. Détection en boucle avec seuil abaissé ─────────────────────────
    //  scoreThreshold 0.3 (défaut 0.5) → détecte mieux sous éclairage moyen
    const options = new faceapi.TinyFaceDetectorOptions({ scoreThreshold: 0.3, inputSize: 320 });

    let attempts = 0;
    const MAX_ATTEMPTS = 30; // 30 × 500 ms = 15 secondes max

    const scanInterval = setInterval(async () => {
        attempts++;

        // Timeout de sécurité
        if (attempts > MAX_ATTEMPTS) {
            clearInterval(scanInterval);
            if (stream) stream.getTracks().forEach(t => t.stop());
            status.innerHTML =
                "<span style='color:red;'>❌ Visage non détecté après 15 s. " +
                "Assurez-vous d'être bien éclairé et face à la caméra, puis réessayez.</span>";
            return;
        }

        // Vérifier que la vidéo diffuse bien
        if (video.readyState < 2 || video.videoWidth === 0) return;

        try {
            const detection = await faceapi
                .detectSingleFace(video, options)
                .withFaceLandmarks()
                .withFaceDescriptor();

            if (detection) {
                clearInterval(scanInterval);
                if (stream) stream.getTracks().forEach(t => t.stop());

                status.innerHTML =
                    "<span style='color:green;'>✅ Visage détecté ! Enregistrement en cours...</span>";

                await saveDescriptor(detection.descriptor);
            } else {
                // Feedback visuel de la progression
                const dots = '.'.repeat((attempts % 3) + 1);
                status.innerHTML = "👤 Recherche du visage" + dots +
                    " <small style='color:#999'>(" + attempts + "/" + MAX_ATTEMPTS + ")</small>";
            }
        } catch (err) {
            console.error("Erreur détection :", err);
            // On ne stoppe pas la boucle pour une erreur passagère
        }

    }, 500); // toutes les 500 ms
}

// ── SAUVEGARDER LE DESCRIPTEUR ────────────────────────────────────────────────
async function saveDescriptor(descriptor) {
    const userId = getSessionUserId(); // fonction déjà présente dans la page
    const status = document.getElementById('enroll-status');

    try {
        const formData = new FormData();
        formData.append('action',     'saveFaceDescriptor');
        formData.append('id_user',    userId);
        formData.append('descriptor', JSON.stringify(Array.from(descriptor)));

        const response = await fetch('../../controleur/backoffice/user_crud.php', {
            method: 'POST',
            body:   formData
        });

        const result = await response.json();

        if (result.success) {
            status.innerHTML = "<span style='color:green;'>✅ Visage enregistré avec succès !</span>";
        } else {
            status.innerHTML = "<span style='color:red;'>❌ Erreur serveur : " + (result.error || 'inconnue') + "</span>";
        }
    } catch (err) {
        status.innerHTML = "<span style='color:red;'>❌ Erreur réseau : " + err.message + "</span>";
        console.error("Erreur saveDescriptor :", err);
    }
}
