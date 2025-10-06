<form id="quicksearch-form" onsubmit="return false;">
    <div class="quicksearch-wrapper">
        <i class="fa fa-search"></i>
        <input type="text" id="quicksearch-input" placeholder="Search..." autocomplete="off">
    </div>
    <div id="quicksearch-results"></div>
</form>
<script>
    const input = document.getElementById('quicksearch-input');
    const results = document.getElementById('quicksearch-results');

    input.addEventListener('focus', () => {
        input.classList.add('expanded');
    });

    input.addEventListener('input', function (event) {
        const query = this.value.trim();

        if (query.length < 2) {
            results.innerHTML = '';
            return;
        }

        fetch(`../common/quicksearch_api.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                let resultsHTML = '';
                data.forEach(item => {
                    let link = '#';

                    if (item.type === 'Member') link = `../members/lookup.php?id=${item.id}`;
                    if (item.type === 'Page') link = `../${item.details}`;
                    if (item.type === 'Primary Contact') link = `../members/contacts.php?id=${item.id}`;
                    if (item.type === 'Secondary Contact') link = `../members/contacts.php?id=${item.id}`;

                    resultsHTML += `
                        <div class="search-result">
                            <img src="${item.image}" onerror="this.onerror=null;this.src='../images/noprofilepic.jpeg';" class="${item.addClass}">
                            <div class="text-content">
                                <a href="${link}" target="_blank" class="name">${item.name}</a>
                                <span class="type">${item.type} - ${item.details}</span>
                            </div>
                        </div>
                    `;
                });
                results.innerHTML = resultsHTML;
            })
            .catch(error => console.error("Error fetching search results:", error));
    });

    // Collapse input and hide results when clicking outside
    document.addEventListener('click', function (event) {
        if (!input.contains(event.target) && !results.contains(event.target)) {
            results.innerHTML = '';
            input.classList.remove('expanded');
            input.value = ''; // ← This clears the input
        }
    });

    // Optional: Close results on clicking a result
    results.addEventListener('click', function (event) {
        if (event.target.closest('a')) {
            results.innerHTML = '';
            input.classList.remove('expanded');
        }
    });
</script>
