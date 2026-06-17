function sortTable(table, column, asc) {
    asc = sortDirections[column];
    rows = Array.prototype.slice.call(table.rows);
    rows.sort((a, b) => {
        if (a.getElementsByTagName("TD").length == 0 || b.getElementsByTagName("TD").length == 0) {
            return 0;
        }
        A = a.getElementsByTagName("TD")[column].innerHTML.toLowerCase().trim();
        B = b.getElementsByTagName("TD")[column].innerHTML.toLowerCase().trim();
        Anum = parseInt(A);
        Bnum = parseInt(B);
        if (!isNaN(Anum) && !isNaN(Bnum)) {
            if (asc) {
                return Anum - Bnum;
            } else {
                return Bnum - Anum;
            }
        }
        if (asc) {
            return A.localeCompare(B);
        } else {
            return B.localeCompare(A);
        }
    })
    fragment = document.createDocumentFragment();
    rows.forEach(row => fragment.appendChild(row));
    table.appendChild(fragment);
    sortDirections[column] = !asc; // toggle sort direction for next click
}
function getArticle(hash = null,  id = null) {
    if (hash) {
        content = fetch(`article.php?hash=${hash}`).then(response => response.text());
    } else if (id) {
        content = fetch(`article.php?id=${id}`).then(response => response.text());
    } else {
        return;
    }
    return content;
}
loadArticle = (hash = null, id = null) => {
    getArticle(hash, id).then(content => {
        document.getElementById("articleContent").innerHTML = content;
    });
}
function switchLayout() {
    list = document.getElementById("article-list");
    grid = document.getElementById("article-grid");
    btn = document.getElementById("floating-btn");
    if (list.style.display === "none") {
        list.style.display = "block";
        grid.style.display = "none";
        btn.innerHTML = "&#x25A6;";
    } else {
        list.style.display = "none";
        grid.style.display = "grid";
        btn.innerHTML = "&#x25A4;";
    }
}