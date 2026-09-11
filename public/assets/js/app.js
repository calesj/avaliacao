document.addEventListener('DOMContentLoaded', () => {
    fetch('/api/customers')
        .then(response => response.json())
        .then(dados => {

            let html = '<table border="1" cellpadding="5">';
            html += '<tr><th>Nome</th><th>Email</th><th>Cidade</th><th>Telefone</th></tr>';

            dados.forEach(cliente => {
                html += `
                    <tr>
                        <td>${cliente.nome}</td>
                        <td>${cliente.email}</td>
                        <td>${cliente.cidade}</td>
                        <td>${cliente.telefone}</td>
                    </tr>
                `;
            });

            html += '</table>';
            document.getElementById('relatorios').innerHTML = html;
        });
});
