document.addEventListener("DOMContentLoaded", () => {
  const toggle = document.getElementById("hubPanelToggle");
  const card = document.getElementById("hubPanelCard");

  if (!toggle || !card) return;

  // Abre / fecha no clique
  toggle.addEventListener("click", (e) => {
    e.stopPropagation();
    card.classList.toggle("open");
  });

  // Fecha ao clicar fora
  document.addEventListener("click", () => {
    card.classList.remove("open");
  });

  // Evita fechar ao clicar dentro do card
  card.addEventListener("click", (e) => {
    e.stopPropagation();
  });
});
