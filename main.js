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