let students = [];
let filteredStudents = [];

let currentPage = 1;
let studentsPerPage = 2;

fetch("students.json")
    .then(response => response.json())
    .then(data => {

        students = data;
        filteredStudents = data;

        displayStudents();

    })
    .catch(error => {

        console.log("Error loading JSON:", error);

    });

function displayStudents() {

    let list = document.getElementById("studentList");

    list.innerHTML = "";

    let start = (currentPage - 1) * studentsPerPage;

    let end = start + studentsPerPage;

    let pageStudents = filteredStudents.slice(start, end);


    pageStudents.forEach(student => {

        let card = `
            <div class="card">

                <h3>${student.name}</h3>

                <p>ID: ${student.id}</p>

                <p>Course: ${student.course}</p>

                <p>Year: ${student.year}</p>

            </div>
        `;

        list.innerHTML += card;

    });

    displayPagination();
}


document.getElementById("search").addEventListener("input", function () {

    let searchText = this.value.toLowerCase();

    filteredStudents = students.filter(student =>
        student.name.toLowerCase().includes(searchText)
    );

    currentPage = 1;

    displayStudents();

});

document.getElementById("courseFilter").addEventListener("change", function () {

    let course = this.value;

    if (course === "all") {

        filteredStudents = students;

    } else {

        filteredStudents = students.filter(student =>
            student.course === course
        );

    }

    currentPage = 1;

    displayStudents();

});


document.getElementById("sort").addEventListener("change", function () {

    let sortType = this.value;

    if (sortType === "az") {

        filteredStudents.sort((a, b) =>
            a.name.localeCompare(b.name)
        );

    }

    else if (sortType === "za") {

        filteredStudents.sort((a, b) =>
            b.name.localeCompare(a.name)
        );

    }

    displayStudents();

});

function displayPagination() {

    let pagination = document.getElementById("pagination");

    pagination.innerHTML = "";

    let totalPages = Math.ceil(
        filteredStudents.length / studentsPerPage
    );


    for (let i = 1; i <= totalPages; i++) {

        let button = document.createElement("button");

        button.innerText = i;

        button.onclick = function () {

            currentPage = i;

            displayStudents();

        };

        pagination.appendChild(button);

    }

}