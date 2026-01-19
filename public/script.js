document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('main-content');
    const navLinks = document.querySelectorAll('.sidebar-nav a');

    const loadPage = async (page) => {
        try {
            const response = await fetch(`${page}.html`);
            const html = await response.text();
            mainContent.innerHTML = html;
            loadDynamicContent(page);
            updateActiveLink(page);
        } catch (error) {
            mainContent.innerHTML = '<p>Error loading page.</p>';
        }
    };

    const loadDynamicContent = (page) => {
        if (page === 'laboratory' || page === 'practical' || page === 'essays' || page === 'otrabotka' || page === 'narabotka' || page === 'kursovie') {
            loadWorks(page);
            setupWorkModal(page);
        } else if (page === 'sites') {
            loadSites();
            setupSiteModal();
        }
    };

    const loadWorks = async (type) => {
        const response = await fetch(`/api/get_works.php?type=${type}`);
        const works = await response.json();
        const container = document.getElementById('cards-container');
        container.innerHTML = '';
        works.forEach(work => {
            const card = document.createElement('div');
            card.className = 'card';
            if (type === 'kursovie') {
                card.innerHTML = `
                    <h3>${work.name}</h3>
                    <p>Дата выполнения: ${work.date}</p>
                    <button class="download-button" data-link="${work.doc_file_link}">Скачать документацию</button>
                    <button class="download-button" data-link="${work.zip_file_link}">Скачать архив</button>
                    <button class="delete-button" data-id="${work.id}">Удалить</button>
                `;
            } else {
                card.innerHTML = `
                    <h3>${work.name}</h3>
                    <p>Дата выполнения: ${work.date}</p>
                    <button class="download-button" data-link="${work.file_link}">Скачать отчет</button>
                    <button class="delete-button" data-id="${work.id}">Удалить</button>
                `;
            }
            container.appendChild(card);

            card.querySelectorAll('.download-button').forEach(button => {
                button.addEventListener('click', () => {
                    window.open(button.dataset.link, '_blank');
                });
            });

            card.querySelector('.delete-button').addEventListener('click', async () => {
                const password = prompt('Введите пароль для удаления:');
                if (!password) return;

                const id = work.id;
                const response = await fetch(`/api/delete_work.php?type=${type}&id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password })
                });

                if (response.ok) {
                    loadWorks(type);
                } else if (response.status === 401) {
                    alert('Неверный пароль.');
                } else {
                    alert('Не удалось удалить работу.');
                }
            });
        });
    };

    const loadSites = async () => {
        const response = await fetch('/api/get_sites.php');
        const sites = await response.json();
        const container = document.getElementById('cards-container');
        container.innerHTML = '';
        sites.forEach(site => {
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = `
                <h3>${site.name}</h3>
                <p><a href="${site.site_link}" target="_blank">Посетить сайт</a></p>
                <p><a href="${site.figma_link}" target="_blank">Макет в Figma</a></p>
                <button class="delete-button" data-id="${site.id}">Удалить</button>
            `;
            container.appendChild(card);

            card.querySelector('.delete-button').addEventListener('click', async () => {
                const password = prompt('Введите пароль для удаления:');
                if (!password) return;

                const id = site.id;
                const response = await fetch(`/api/delete_site.php?id=${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password })
                });
                
                if (response.ok) {
                    loadSites();
                } else if (response.status === 401) {
                    alert('Неверный пароль.');
                } else {
                    alert('Не удалось удалить сайт.');
                }
            });
        });
    };

    const setupWorkModal = (type) => {
        const modal = document.getElementById('add-work-modal');
        const addButton = document.getElementById('add-card-button');
        const closeButton = modal.querySelector('.close-button');
        const form = document.getElementById('add-work-form');

        addButton.onclick = () => {
            modal.style.display = 'block';
        };

        closeButton.onclick = () => {
            modal.style.display = 'none';
        };

        window.onclick = (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };

        form.onsubmit = async (event) => {
            event.preventDefault();
            
            const password = prompt('Введите пароль для добавления:');
            if (!password) return;

            const formData = new FormData(form);
            formData.append('password', password);
            formData.append('type', type);

            const response = await fetch(`/api/add_work.php`, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                modal.style.display = 'none';
                form.reset();
                loadWorks(type);
            } else if (response.status === 401) {
                alert('Неверный пароль.');
            } else {
                alert('Не удалось добавить работу.');
            }
        };
    };

    const setupSiteModal = () => {
        const modal = document.getElementById('add-site-modal');
        const addButton = document.getElementById('add-card-button');
        const closeButton = modal.querySelector('.close-button');
        const form = document.getElementById('add-site-form');

        addButton.onclick = () => {
            modal.style.display = 'block';
        };

        closeButton.onclick = () => {
            modal.style.display = 'none';
        };

        window.onclick = (event) => {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        };

        form.onsubmit = async (event) => {
            event.preventDefault();

            const password = prompt('Введите пароль для добавления:');
            if (!password) return;

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.password = password;
            
            const response = await fetch('/api/add_site.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (response.ok) {
                modal.style.display = 'none';
                form.reset();
                loadSites();
            } else if (response.status === 401) {
                alert('Неверный пароль.');
            } else {
                alert('Не удалось добавить сайт.');
            }
        };
    };

    const updateActiveLink = (page) => {
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.dataset.page === page) {
                link.classList.add('active');
            }
        });
    };

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = link.dataset.page;
            loadPage(page);
        });
    });

    // Load the default page
    loadPage('essays');
});
