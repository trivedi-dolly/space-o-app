<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News API in Laravel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

</head>

<body class="bg-gray-100">

    <div class="container mx-auto p-8">

        <div class="flex items-center mb-4">
            <label class="mr-4">Customize Columns:</label>
            <div class="flex items-center">
                <input type="checkbox" id="toggleImage" class="mr-1" checked>
                <label for="toggleImage" class="mr-4">Image</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="toggleTitle" class="mr-1" checked>
                <label for="toggleTitle" class="mr-4">Title</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="toggleContent" class="mr-1" checked>
                <label for="toggleContent" class="mr-4">Content</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="togglePublishedAt" class="mr-1" checked>
                <label for="togglePublishedAt" class="mr-4">Published At</label>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="toggleAuthor" class="mr-1" checked>
                <label for="toggleAuthor">Author</label>
            </div>
        </div>

        <div class="flex flex-row items-center mb-4">
            <form id="searchForm" class="flex">
                <input type="text" name="search" placeholder="Search..." id="searchInput"
                    class="border border-gray-300 rounded-l px-4 py-2 focus:outline-none focus:border-blue-500">
                <button type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded-r hover:bg-blue-600 focus:outline-none">Search</button>
            </form>

            <div class="ml-4">
                <label for="publishedAtFilter" class="mr-2">Filter by Publish Date:</label>
                <input type="text" id="publishedAtFilter" placeholder="Select Date Range"
                    class="border border-gray-300 rounded-l px-4 py-2 focus:outline-none focus:border-blue-500">
            </div>
        </div>


        <div class="overflow-x-auto">
            <table id="newsTable" class="table-auto min-w-full">
                <thead>
                    <tr>
                        <th class="px-6 py-3">Image</th>
                        <th class="px-6 py-3" id="title">Title</th>
                        <th class="px-6 py-3" id="content">Content</th>
                        <th class="px-6 py-3" id="published_at">Published At</th>
                        <th class="px-6 py-3" id="author">Author</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($newsPaginated as $news)
                    <tr>
                        <td class="px-6 py-4">
                            <img src="{{ $news['urlToImage'] }}" class="h-16 w-16 object-cover" alt="...">
                        </td>
                        <td class="px-6 py-4">{{ $news['title'] }}</td>
                        <td class="px-6 py-4">{{ $news['content'] }}</td>
                        <td class="px-6 py-4">{{ date('d-m-Y', strtotime($news['publishedAt'])) }}</td>
                        <td class="px-6 py-4">{{ $news['author'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $newsPaginated->links() }}
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let columnVisibility = JSON.parse(localStorage.getItem('columnVisibility')) || {
                image: true,
                title: true,
                content: true,
                publishedAt: true,
                author: true
            };

            function updateColumnVisibility() {
                document.getElementById('newsTable').querySelectorAll('thead th').forEach((th, index) => {
                    const columnName = th.textContent.trim().replace(/\s+/g, '').toLowerCase();
                    th.style.display = columnVisibility[columnName] ? '' : 'none';
                    document.querySelectorAll(`#newsTable td:nth-child(${index + 1})`).forEach(td => {
                        td.style.display = columnVisibility[columnName] ? '' : 'none';
                    });
                });
            }
            document.querySelectorAll('[id^=toggle]').forEach(checkbox => {
                const columnName = checkbox.id.replace('toggle', '').toLowerCase();
                checkbox.checked = columnVisibility[columnName]; 
            });

            updateColumnVisibility();

            document.querySelectorAll('[id^=toggle]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const columnName = this.id.replace('toggle', '').toLowerCase();
                    columnVisibility[columnName] = this.checked;
                    localStorage.setItem('columnVisibility', JSON.stringify(columnVisibility));
                    updateColumnVisibility();
                });
            });
            document.querySelectorAll('th').forEach(header => {
                header.addEventListener('click', function() {
                    const columnIndex = Array.from(this.parentNode.children).indexOf(this);
                    const rows = Array.from(document.querySelectorAll('#newsTable tbody tr'));
                    const isDateColumn = this.id.includes('published_at');

                    rows.sort((a, b) => {
                        const valueA = isDateColumn ? new Date(a.children[columnIndex]
                                .textContent.trim()) : a.children[columnIndex].textContent
                            .trim();
                        const valueB = isDateColumn ? new Date(b.children[columnIndex]
                                .textContent.trim()) : b.children[columnIndex].textContent
                            .trim();

                        if (isDateColumn) {
                            return valueA - valueB;
                        } else {
                            return valueA.localeCompare(valueB);
                        }
                    });


                    if (this.classList.contains('sorted-asc')) {
                        rows.reverse();
                        this.classList.remove('sorted-asc');
                        this.classList.add('sorted-desc');
                    } else {
                        document.querySelectorAll('th').forEach(otherHeader => {
                            if (otherHeader !== header) {
                                otherHeader.classList.remove('sorted-asc', 'sorted-desc');
                            }
                        });

                        this.classList.remove('sorted-desc');
                        this.classList.add('sorted-asc');
                    }

                    const tbody = document.querySelector('tbody');
                    tbody.innerHTML = ""; 
                    rows.forEach(row => {
                        tbody.appendChild(row);
                    });
                });
            });

            document.getElementById('searchForm').addEventListener('submit', function(event) {
                event.preventDefault();
                const searchKeyword = document.getElementById('searchInput').value.trim();

                fetch(`/search?keyword=${searchKeyword}`)
                    .then(response => response.json())
                    .then(data => {
                        const tbody = document.querySelector('tbody');
                        tbody.innerHTML = "";
                        console.log("data", data);

                        for (const key in data) {
                            if (Object.hasOwnProperty.call(data, key)) {
                                const news = data[key];
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                        <td class="px-6 py-4">
                                            <img src="${news.urlToImage}" class="h-16 w-16 object-cover" alt="...">
                                        </td>
                                        <td class="px-6 py-4">${news.title}</td>
                                        <td class="px-6 py-4">${news.content}</td>
                                        <td class="px-6 py-4">${news.publishedAt}</td>
                                        <td class="px-6 py-4">${news.author}</td>
                                    `;
                                tbody.appendChild(row);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                    });
            });

            flatpickr('#publishedAtFilter', {
                mode: 'range', // Enable selection of date range
                dateFormat: 'Y-m-d', // Date format
                onChange: handleFilterChange // Trigger filter change on date selection
            });

            function handleFilterChange(selectedDates, dateStr, instance) {
                const startDate = selectedDates[0] ? selectedDates[0].toISOString() : '';
                const endDate = selectedDates[1] ? selectedDates[1].toISOString() : '';

                fetch(`/filter?startDate=${startDate}&endDate=${endDate}`)
                    .then(response => response.json())
                    .then(data => {
                        const tbody = document.querySelector('tbody');
                        tbody.innerHTML = "";
                        console.log("data", data);

                        for (const key in data) {
                            if (Object.hasOwnProperty.call(data, key)) {
                                const news = data[key];
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                        <td class="px-6 py-4">
                                            <img src="${news.urlToImage}" class="h-16 w-16 object-cover" alt="...">
                                        </td>
                                        <td class="px-6 py-4">${news.title}</td>
                                        <td class="px-6 py-4">${news.content}</td>
                                        <td class="px-6 py-4">${news.publishedAt}</td>
                                        <td class="px-6 py-4">${news.author}</td>
                                    `;
                                tbody.appendChild(row);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching filtered results:', error);
                    });
            }
        });
    </script>
</body>

</html>