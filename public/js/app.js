const app = {
    baseUrl: 'http://localhost:8080',

    init() {
        this.setupTabs();
    },

    setupTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.getAttribute('data-tab');

                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));

                button.classList.add('active');
                document.getElementById(tabName).classList.add('active');
            });
        });
    },

    setLoading(element, message) {
        element.textContent = '';
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'loading';
        element.appendChild(loadingDiv);
        const text = document.createTextNode(' ' + message);
        element.appendChild(text);
    },

    createPre(content) {
        const pre = document.createElement('pre');
        pre.textContent = JSON.stringify(content, null, 2);
        return pre;
    },

    async checkHealth() {
        const resultDiv = document.getElementById('health-result');
        this.setLoading(resultDiv, 'Verificando...');

        try {
            const response = await fetch(`${this.baseUrl}/health`);
            const data = await response.json();

            resultDiv.textContent = '';

            const statusP = document.createElement('p');
            statusP.className = response.ok ? 'success' : 'error';
            statusP.textContent = response.ok ? '✓ API está funcionando!' : '✗ Erro na API';
            resultDiv.appendChild(statusP);
            resultDiv.appendChild(this.createPre(data));
        } catch (error) {
            resultDiv.textContent = '';
            const errorP = document.createElement('p');
            errorP.className = 'error';
            errorP.textContent = '✗ Erro ao conectar com a API';
            resultDiv.appendChild(errorP);
            const errorMsg = document.createElement('p');
            errorMsg.textContent = error.message;
            resultDiv.appendChild(errorMsg);
        }
    },

    async loadMovements() {
        const resultDiv = document.getElementById('movements-result');
        this.setLoading(resultDiv, 'Carregando...');

        try {
            const response = await fetch(`${this.baseUrl}/movements`);
            const json = await response.json();

            resultDiv.textContent = '';

            if (response.ok && json.data) {
                const table = document.createElement('table');
                const thead = document.createElement('thead');
                const headerRow = document.createElement('tr');

                ['ID', 'Nome do Movimento'].forEach(text => {
                    const th = document.createElement('th');
                    th.textContent = text;
                    headerRow.appendChild(th);
                });

                thead.appendChild(headerRow);
                table.appendChild(thead);

                const tbody = document.createElement('tbody');
                json.data.forEach(movement => {
                    const row = document.createElement('tr');

                    const idCell = document.createElement('td');
                    idCell.textContent = movement.id;
                    row.appendChild(idCell);

                    const nameCell = document.createElement('td');
                    nameCell.textContent = movement.name;
                    row.appendChild(nameCell);

                    tbody.appendChild(row);
                });

                table.appendChild(tbody);
                resultDiv.appendChild(table);
            } else {
                const errorP = document.createElement('p');
                errorP.className = 'error';
                errorP.textContent = '✗ Erro ao carregar movimentos';
                resultDiv.appendChild(errorP);
                resultDiv.appendChild(this.createPre(json));
            }
        } catch (error) {
            resultDiv.textContent = '';
            const errorP = document.createElement('p');
            errorP.className = 'error';
            errorP.textContent = '✗ Erro ao conectar com a API';
            resultDiv.appendChild(errorP);
            const errorMsg = document.createElement('p');
            errorMsg.textContent = error.message;
            resultDiv.appendChild(errorMsg);
        }
    },

    async loadRanking(movementId, tab) {
        const limitInput = document.getElementById(`${tab}-limit`);
        const limit = parseInt(limitInput.value) || 10;
        const resultDiv = document.getElementById(`${tab}-result`);

        this.setLoading(resultDiv, 'Carregando ranking...');

        try {
            const url = `${this.baseUrl}/ranking?movement_id=${movementId}&limit=${limit}`;
            const response = await fetch(url);
            const json = await response.json();

            resultDiv.textContent = '';

            if (response.ok && json.data) {
                const { movement_name, total_users, ranking } = json.data;

                const title = document.createElement('h3');
                title.textContent = movement_name;
                resultDiv.appendChild(title);

                const totalP = document.createElement('p');
                totalP.textContent = 'Total de usuários: ';
                const strong = document.createElement('strong');
                strong.textContent = total_users;
                totalP.appendChild(strong);
                resultDiv.appendChild(totalP);

                const table = document.createElement('table');
                const thead = document.createElement('thead');
                const headerRow = document.createElement('tr');

                ['Posição', 'Usuário', 'Record (kg)', 'Data'].forEach(text => {
                    const th = document.createElement('th');
                    th.textContent = text;
                    headerRow.appendChild(th);
                });

                thead.appendChild(headerRow);
                table.appendChild(thead);

                const tbody = document.createElement('tbody');
                ranking.forEach(entry => {
                    let medal = '';
                    if (entry.position === 1) medal = '🥇 ';
                    else if (entry.position === 2) medal = '🥈 ';
                    else if (entry.position === 3) medal = '🥉 ';

                    const row = document.createElement('tr');

                    const posCell = document.createElement('td');
                    posCell.className = 'position';
                    posCell.textContent = medal + entry.position;
                    row.appendChild(posCell);

                    const nameCell = document.createElement('td');
                    nameCell.textContent = entry.user_name;
                    row.appendChild(nameCell);

                    const recordCell = document.createElement('td');
                    const recordStrong = document.createElement('strong');
                    recordStrong.textContent = entry.personal_record + ' kg';
                    recordCell.appendChild(recordStrong);
                    row.appendChild(recordCell);

                    const dateCell = document.createElement('td');
                    dateCell.textContent = this.formatDate(entry.record_date);
                    row.appendChild(dateCell);

                    tbody.appendChild(row);
                });

                table.appendChild(tbody);
                resultDiv.appendChild(table);
            } else {
                const errorP = document.createElement('p');
                errorP.className = 'error';
                errorP.textContent = '✗ Erro ao carregar ranking';
                resultDiv.appendChild(errorP);
                resultDiv.appendChild(this.createPre(json));
            }
        } catch (error) {
            resultDiv.textContent = '';
            const errorP = document.createElement('p');
            errorP.className = 'error';
            errorP.textContent = '✗ Erro ao conectar com a API';
            resultDiv.appendChild(errorP);
            const errorMsg = document.createElement('p');
            errorMsg.textContent = error.message;
            resultDiv.appendChild(errorMsg);
        }
    },

    formatDate(dateString) {
        const [year, month, day] = dateString.split('-');
        return `${day}/${month}/${year}`;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
