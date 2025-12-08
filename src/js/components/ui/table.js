// src/js/components/ui/table.js
document.addEventListener("DOMContentLoaded", function() {
    const tableData = [
        { id: 1, name: "Product 1", price: "$10", quantity: 2 },
        { id: 2, name: "Product 2", price: "$20", quantity: 1 },
        { id: 3, name: "Product 3", price: "$30", quantity: 5 },
    ];

    const tableBody = document.getElementById("table-body");

    tableData.forEach(item => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.name}</td>
            <td>${item.price}</td>
            <td>${item.quantity}</td>
        `;
        tableBody.appendChild(row);
    });
});

// Sample HTML structure for the table
// <table class="table">
//     <thead>
//         <tr>
//             <th>ID</th>
//             <th>Name</th>
//             <th>Price</th>
//             <th>Quantity</th>
//         </tr>
//     </thead>
//     <tbody id="table-body">
//         <!-- Rows will be populated here -->
//     </tbody>
// </table>