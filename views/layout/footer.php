  </main> <!-- Fecha área principal -->
</div> <!-- Fecha container flex -->

<!-- Feather Icons -->
<script src="https://unpkg.com/feather-icons"></script>
<script>
  feather.replace();
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Script custom -->
<script>
  // Exemplo: gráfico de assinaturas
  const ctx = document.getElementById('assinaturasChart');
  if (ctx) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
        datasets: [{
          label: 'Assinaturas Ativas',
          data: [3, 6, 4, 8, 10, 12],
          borderColor: '#4f46e5',
          backgroundColor: 'rgba(79,70,229,0.1)',
          borderWidth: 2,
          tension: 0.3,
          fill: true
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true, labels: { color: '#374151' } }
        },
        scales: {
          x: { ticks: { color: '#6b7280' } },
          y: { ticks: { color: '#6b7280' } }
        }
      }
    });
  }
</script>
</body>
</html>