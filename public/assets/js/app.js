const searchInput = document.getElementById('busca');
const counter = document.getElementById('contagem');
const table = document.getElementById('tabela');
const tableBody = document.getElementById('corpo');
const loading = document.getElementById('estado-carregando');
const errorBox = document.getElementById('estado-erro');
const emptyBox = document.getElementById('estado-vazio');

document.getElementById('filtros').addEventListener('submit', (event) => {
    event.preventDefault();
    load();
});

document.getElementById('tentar-novamente').addEventListener('click', load);

load();

async function load() {
    loading.hidden = false;
    table.hidden = true;
    errorBox.hidden = true;
    emptyBox.hidden = true;

    try {

        /** Timeout de 1 segundo pra ver o loading na table */
        await new Promise(resolve => setTimeout(resolve, 1000));

        const response = await fetch(`/api/customers?busca=${encodeURIComponent(searchInput.value)}`);

        // fetch não rejeita em erro HTTP: sem isto um 500 cairia no .json(),
        // viraria erro de parse e a tela ficaria branca.
        if (!response.ok) {
            throw new Error(`O servidor respondeu ${response.status}.`);
        }

        const customers = await response.json();

        // A fonte é um array versionado no próprio projeto, sem entrada de
        // usuário, então interpolar direto é seguro aqui. Se um dia os dados
        // vierem de formulário ou banco, trocar por textContent.
        tableBody.innerHTML = customers.map((customer) => `
            <tr>
                <td>${customer.nome}</td>
                <td>${customer.email}</td>
                <td>${customer.cidade}</td>
                <td>${formatPhone(customer.telefone)}</td>
            </tr>
        `).join('');

        counter.textContent = `${customers.length} ${customers.length === 1 ? 'cliente' : 'clientes'}`;

        loading.hidden = true;
        table.hidden = customers.length === 0;
        emptyBox.hidden = customers.length > 0;
    } catch (failure) {
        document.getElementById('erro-detalhe').textContent = failure.message;

        loading.hidden = true;
        errorBox.hidden = false;
    }
}

function formatPhone(phone) {
    return String(phone).replace(/^(\d{2})(\d{4,5})(\d{4})$/, '($1) $2-$3');
}
