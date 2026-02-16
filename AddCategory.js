document.querySelectorAll(".cat-item").forEach(item => {
    item.addEventListener("click", function (e) {
        e.stopPropagation();

        const parentId = this.dataset.id; // data-id
        const parentName = this.innerText;

        document.getElementById("parent_id").value = parentId;
        document.querySelector(".dropdown-title").innerText = parentName + " ▾";
    });
});

