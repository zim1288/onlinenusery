document.addEventListener("DOMContentLoaded", () => {
  const plantList = document.getElementById("plant-list");
  const orderForm = document.getElementById("orderForm");
  const plantForm = document.getElementById("plantForm");
  const orderResponse = document.getElementById("order-response");
  const manageResponse = document.getElementById("manage-response");

  const plantIdInput = document.getElementById("plantId");
  const plantNameInput = document.getElementById("plantName");
  const plantPriceInput = document.getElementById("plantPrice");
  const clearBtn = document.getElementById("clearBtn");

  // READ: Fetch and render all plants
  async function fetchAndRenderPlants() {
    try {
      const res = await fetch("api/get_plants.php");
      const plants = await res.json();

      plantList.innerHTML = "";
      plants.forEach((plant) => {
        const div = document.createElement("div");
        div.className = "plant-item";
        div.setAttribute("data-id", plant.id);
        div.innerHTML = `
                    <strong>${plant.name}</strong><br>
                    Price: $${parseFloat(plant.price).toFixed(2)}
                    <div class="plant-actions">
                        <button class="edit-btn">Edit</button>
                        <button class="delete-btn">Delete</button>
                    </div>
                `;
        plantList.appendChild(div);
      });
    } catch (error) {
      plantList.innerHTML = "<p>Could not fetch plants.</p>";
    }
  }

  // Event listener for managing plants (Edit and Delete) using event delegation
  plantList.addEventListener("click", (e) => {
    const target = e.target;
    const plantItem = target.closest(".plant-item");
    const id = plantItem.dataset.id;

    if (target.classList.contains("edit-btn")) {
      // Populate form for editing
      const name = plantItem.querySelector("strong").textContent;
      const price = plantItem.innerHTML.match(/Price: \$(\d+\.\d+)/)[1];

      plantIdInput.value = id;
      plantNameInput.value = name;
      plantPriceInput.value = price;
      window.location.hash = "manage"; // Scroll to the form
    }

    if (target.classList.contains("delete-btn")) {
      // DELETE: Handle plant deletion
      if (confirm("Are you sure you want to delete this plant?")) {
        const formData = new FormData();
        formData.append("id", id);

        fetch("api/delete_plant.php", { method: "POST", body: formData })
          .then((res) => res.text())
          .then((data) => {
            manageResponse.textContent = data;
            fetchAndRenderPlants(); // Refresh the list
          });
      }
    }
  });

  // CREATE / UPDATE: Handle the plant management form
  plantForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = formData.get("id");

    // If an ID exists, it's an update; otherwise, it's an add.
    const url = id ? "api/update_plant.php" : "api/add_plant.php";

    fetch(url, { method: "POST", body: formData })
      .then((res) => res.text())
      .then((data) => {
        manageResponse.textContent = data;
        fetchAndRenderPlants(); // Refresh the list
        this.reset();
        plantIdInput.value = ""; // Ensure hidden ID is cleared
      });
  });

  // Clear the management form
  clearBtn.addEventListener("click", () => {
    plantForm.reset();
    plantIdInput.value = "";
  });

  // Handle customer order form submission
  orderForm.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch("order.php", { method: "POST", body: formData })
      .then((res) => res.text())
      .then((data) => {
        orderResponse.textContent = data;
        this.reset();
        setTimeout(() => (orderResponse.textContent = ""), 5000); // Clear message after 5s
      })
      .catch(() => {
        orderResponse.textContent = "Order failed. Please try again.";
      });
  });

  // Initial load
  fetchAndRenderPlants();
});
