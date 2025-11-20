</main>

<footer>
    &copy; <?= date('Y') ?> Télédispensaire. Tous droits réservés.
</footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const bell = document.getElementById("notif-bell");
    const notifCount = document.getElementById("notif-count");

    if (bell) {
        bell.addEventListener("click", async () => {
            try {
                // Marquer toutes les notifications comme lues
                await fetch('/telesante/includes/mark_read.php', { method: 'POST' });
                notifCount.style.display = "none";

                // Afficher une popup visuelle
                const popup = document.createElement("div");
                popup.textContent = "✅ Toutes les notifications ont été marquées comme lues.";
                popup.style.position = "fixed";
                popup.style.bottom = "20px";
                popup.style.right = "20px";
                popup.style.background = "#2ecc71";
                popup.style.color = "white";
                popup.style.padding = "10px 20px";
                popup.style.borderRadius = "8px";
                popup.style.boxShadow = "0 2px 8px rgba(0,0,0,0.2)";
                popup.style.zIndex = "9999";
                document.body.appendChild(popup);
                setTimeout(() => popup.remove(), 4000);
            } catch (e) {
                console.error("Erreur lors du marquage des notifications :", e);
            }
        });
    }
});
</script>

</body>
</html>
