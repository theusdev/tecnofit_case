const app = {
    baseUrl: 'http://localhost:8080',
    lastJsonResponse: null,

    init() {
        this.setupTabs();
        this.setupSearchTypeToggle();
        this.loadMovements();
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

    setupSearchTypeToggle() {
        const radios = document.querySelectorAll('input[name="search_type"]');
        radios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                const searchIdGroup = document.getElementById('search-id-group');
                const searchNameGroup = document.getElementById('search-name-group');

                if (e.target.value === 'id') {
                    searchIdGroup.style.display = 'block';
                    searchNameGroup.style.display = 'none';
                } else {
                    searchIdGroup.style.display = 'none';
                    searchNameGroup.style.display = 'block';
                }
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

    async loadMovements() {
        try {
            const response = await fetch(`${this.baseUrl}/movements`);
            const json = await response.json();

            if (response.ok && json.data) {
                const select = document.getElementById('movement-id');
                select.textContent = '';

                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = 'Selecione um movimento';
                select.appendChild(defaultOption);

                json.data.forEach(movement => {
                    const option = document.createElement('option');
                    option.value = movement.id;
                    option.textContent = `${movement.id} - ${movement.name}`;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Erro ao carregar movimentos:', error);
        }
    },

    async searchRanking() {
        const searchType = document.querySelector('input[name="search_type"]:checked').value;
        let url;

        if (searchType === 'id') {
            const movementId = document.getElementById('movement-id').value;
            if (!movementId) {
                alert('Selecione um movimento');
                return;
            }
            url = `${this.baseUrl}/api/rankings?movement_id=${movementId}`;
        } else {
            const movementName = document.getElementById('movement-name').value;
            if (!movementName.trim()) {
                alert('Digite o nome do movimento');
                return;
            }
            url = `${this.baseUrl}/api/rankings?movement_name=${encodeURIComponent(movementName)}`;
        }

        const tableResult = document.getElementById('ranking-table-result');
        const chartResult = document.getElementById('ranking-chart-result');
        this.setLoading(tableResult, 'Buscando ranking...');
        chartResult.textContent = '';

        try {
            const response = await fetch(url);
            const json = await response.json();

            this.lastJsonResponse = json;
            this.updateJsonTab(json);

            tableResult.textContent = '';

            if (response.ok && json.data) {
                this.renderRankingTable(tableResult, json.data);
                this.renderBarChart(chartResult, json.data.ranking);
            } else {
                const errorP = document.createElement('p');
                errorP.className = 'error';
                errorP.textContent = json.error?.message || 'Erro ao buscar ranking';
                tableResult.appendChild(errorP);
            }
        } catch (error) {
            tableResult.textContent = '';
            const errorP = document.createElement('p');
            errorP.className = 'error';
            errorP.textContent = `Erro ao conectar com a API: ${error.message}`;
            tableResult.appendChild(errorP);
        }
    },

    renderRankingTable(container, data) {
        const title = document.createElement('h3');
        title.textContent = data.movement.name;
        container.appendChild(title);

        if (data.ranking.length === 0) {
            const emptyP = document.createElement('p');
            emptyP.textContent = 'Nenhum recorde encontrado para este movimento';
            container.appendChild(emptyP);
            return;
        }

        const table = document.createElement('table');
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');

        ['Posição', 'Usuário', 'Recorde (kg)', 'Data do Recorde'].forEach(text => {
            const th = document.createElement('th');
            th.textContent = text;
            headerRow.appendChild(th);
        });

        thead.appendChild(headerRow);
        table.appendChild(thead);

        const tbody = document.createElement('tbody');
        data.ranking.forEach(entry => {
            const row = document.createElement('tr');

            const posCell = document.createElement('td');
            posCell.className = 'position';
            let medal = '';
            if (entry.position === 1) medal = '🥇 ';
            else if (entry.position === 2) medal = '🥈 ';
            else if (entry.position === 3) medal = '🥉 ';
            posCell.textContent = medal + entry.position + 'º';
            row.appendChild(posCell);

            const nameCell = document.createElement('td');
            nameCell.textContent = entry.user.name;
            row.appendChild(nameCell);

            const recordCell = document.createElement('td');
            const recordStrong = document.createElement('strong');
            recordStrong.textContent = entry.personal_record.value + ' kg';
            recordCell.appendChild(recordStrong);
            row.appendChild(recordCell);

            const dateCell = document.createElement('td');
            dateCell.textContent = this.formatDate(entry.personal_record.date);
            row.appendChild(dateCell);

            tbody.appendChild(row);
        });

        table.appendChild(tbody);
        container.appendChild(table);
    },

    renderBarChart(container, ranking) {
        if (ranking.length === 0) return;

        const chartDiv = document.createElement('div');
        chartDiv.className = 'bar-chart';

        const title = document.createElement('h3');
        title.textContent = 'Gráfico de Barras';
        chartDiv.appendChild(title);

        const maxValue = Math.max(...ranking.map(r => r.personal_record.value));

        ranking.forEach(entry => {
            const bar = document.createElement('div');
            bar.className = `bar rank-${Math.min(entry.position, 3)}`;

            const percentage = (entry.personal_record.value / maxValue) * 100;
            bar.style.width = percentage + '%';

            const label = document.createElement('span');
            label.textContent = entry.user.name;
            bar.appendChild(label);

            const value = document.createElement('span');
            value.textContent = entry.personal_record.value + ' kg';
            bar.appendChild(value);

            chartDiv.appendChild(bar);
        });

        container.appendChild(chartDiv);
    },

    updateJsonTab(json) {
        const jsonPre = document.getElementById('json-pre');
        jsonPre.textContent = JSON.stringify(json, null, 2);
    },

    copyJson() {
        if (!this.lastJsonResponse) {
            alert('Nenhuma requisição realizada ainda');
            return;
        }

        const jsonText = JSON.stringify(this.lastJsonResponse, null, 2);
        navigator.clipboard.writeText(jsonText).then(() => {
            alert('JSON copiado para a área de transferência');
        }).catch(err => {
            alert('Erro ao copiar: ' + err.message);
        });
    },

    formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    app.init();
});
