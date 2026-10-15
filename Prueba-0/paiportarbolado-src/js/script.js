function searchTrees() {
    const input = document.getElementById('buscar').value.toLowerCase();
    const rows = document.querySelectorAll('table tr\:not(\:first-child)');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? '' : 'none';
    });
}
fetch('api/arboles.php')
    .then(response => response.json())
    .then(arboles => {
        console.log('Árboles obtenidos desde la API:');
        console.log(arboles);
    })
    .catch(error => {
        console.error('Error al consultar la API:', error);
    });

