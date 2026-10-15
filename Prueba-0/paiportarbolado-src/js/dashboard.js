fetch('api/arboles.php')
    .then(response => response.json())
    .then(arboles => {

        let sanos = 0;
        let enfermos = 0;
        let talados = 0;

        arboles.forEach(arbol => {

            if (arbol.estado === 'sano') {
                sanos++;
            }

            if (arbol.estado === 'enfermo') {
                enfermos++;
            }

            if (arbol.estado === 'talado') {
                talados++;
            }

        });

        const ctx = document.getElementById('graficoEstados');

        new Chart(ctx, {
            type: 'bar',

            data: {
                labels: ['Sanos', 'Enfermos', 'Talados'],

                datasets: [{
                    label: 'Número de árboles',
                    data: [sanos, enfermos, talados],

                    backgroundColor: [
                        'rgba(76, 175, 80, 0.7)',   // verde
                        'rgba(255, 193, 7, 0.7)',   // amarillo
                        'rgba(244, 67, 54, 0.7)'    // rojo
                    ],

                    borderColor: [
                        'rgb(76, 175, 80)',
                        'rgb(255, 193, 7)',
                        'rgb(244, 67, 54)'
                    ],

                    borderWidth: 2
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        labels: {
                            font: {
                                size: 18
                            }
                        }
                    }
                },

                scales: {
                    x: {
                        ticks: {
                            font: {
                                size: 18
                            }
                        }
                    },

                    y: {
                        beginAtZero: true,

                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            }
        });

    })
    .catch(error => {
        console.error('Error al cargar estadísticas:', error);
    });
