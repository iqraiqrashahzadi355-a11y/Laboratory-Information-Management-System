// Generic Search for tables by Name or Patient Name
function addTableSearch(inputId, tableId, columnIndex){
    var input = document.getElementById(inputId);
    if(input){
        input.addEventListener('keyup', function() {
            var filter = this.value.toUpperCase();
            var rows = document.querySelector("#" + tableId + " tbody").rows;
            for (var i = 0; i < rows.length; i++) {
                var cell = rows[i].cells[columnIndex].textContent.toUpperCase();
                rows[i].style.display = cell.indexOf(filter) > -1 ? "" : "none";
            }
        });
    }
}

// Add searches for different pages
addTableSearch('patientSearch','patientsTable',1);
addTableSearch('testSearch','testsTable',1);
