<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$u_id = $_SESSION["u_id"];
$permissions = [
    1 => ["all_access" => true],
    2 => ["waybill.php" => true, "dispatcher.php" => true, "viewsheet.php" => true],
    3 => ["pod.php" => true],
    4 => ["pod.php" => true, "ar.php" => true, "viewsheet.php" => true],
    5 => ["queries.php" => true, "viewsheet.php" => true],
    6 => ["budget.php" => true, "viewsheet.php" => true],
    7 => ["waybill.php" => true, "dispatcher.php" => true, "viewsheet.php" => true],
    8 => ["dispatcher.php" => true],
    9 => ["pod.php" => true]
];

function hasAccess($u_id, $page, $permissions) {
    return isset($permissions[$u_id]["all_access"]) || 
           (isset($permissions[$u_id][$page]) && $permissions[$u_id][$page]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCL - WAYBILL</title>
    <link rel="icon" href="assets/img/pcl.png" type="image/x-icon">

    <link rel="stylesheet" href="assets/css/waybill.css">
</head>
<body>
    <div class="mobile-toggle">☰</div>
    <div class="overlay"></div>

    <div class="loading-screen" id="loading-screen">
        <div class="loader"></div>
        <span>Loading...</span>
    </div>

    <div class="logo-wrapper">
        <img src="assets/img/logo.png" alt="PCL Logo" style="width: 150px; height: auto;">
    </div>

    <div class="sidebar">
        <div class="user-info">
            <div class="name"><?php echo htmlspecialchars($_SESSION["fullname"]); ?></div>
            <div class="role">Role ID: <?php echo htmlspecialchars($_SESSION["u_id"]); ?></div>
        </div>
        <div>
            <div class="metric-section" data-href="landingPage.php">
                <div class="chart-container">
                    <div class="pie-chart">
                        <div class="pie-slice"></div>
                    </div>
                </div>
                <div class="metric-title">UTILIZATION</div>
            </div>
            <div class="metric-section" data-href="available.php">
                <div class="bar-container">
                    <div class="bar bar-1"></div>
                    <div class="bar bar-2"></div>
                    <div class="bar bar-3"></div>
                </div>
                <div class="metric-title">AVAILABLE TDH</div>
            </div>
            <div class="metric-section" data-href="references.php">
                <div class="chart-container">
                    <div class="people-icon">
                        <div class="people-head"></div>
                        <div class="people-body"></div>
                    </div>
                </div>
                <div class="metric-title">REFERENCES</div>
            </div>
        </div>
        
        <a href="logout.php" class="logout-link" id="logout-link">
            <div class="logout-section">
                <div class="logout-icon">←</div>
                <span>Log Out</span>
            </div>
        </a>
    </div>

    <div class="main-content">
        <div class="logo-container">
            <img src="assets/img/logo.png" alt="PCL Logo" style="width: 200px; height: auto;">
        </div>

        <div class="table-grid">
            <div class="pending-trip-section">
                <div class="trip-header">
                    <h2>List of Pending Trip</h2>
                    <button class="add-trip-btn">+ Add Trip</button>
                </div>

                <div class="table-responsive">
                    <table class="trip-table">
                        <thead>
                            <tr>
                                <th>Waybill No.</th>
                                <th>Date</th>
                                <th>FO/PO/STO</th>
                                <th>Delivery Type</th>
                                <th>Amount</th>
                                <th>Source</th>
                                <th>Pick Up</th>
                                <th>Drop Off</th>
                                <th>Rate</th>
                                <th>Call Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="tripTableBody">
                            <?php
                            require_once 'fetch_trips.php';
                            $trips = getPendingTrips();

                            if (count($trips) > 0) {
                                foreach ($trips as $trip) {
                                    echo "<tr data-id='" . $trip['cs_id'] . "'>";
                                    echo "<td>" . $trip['waybill'] . "</td>";
                                    echo "<td>" . date("F j, Y", strtotime($trip['date'])) . "</td>";
                                    echo "<td>" . $trip['status'] . "</td>";
                                    echo "<td>" . $trip['delivery_type'] . "</td>";
                                    echo "<td> ₱ " . (isset($trip['amount']) ? $trip['amount'] : '') . "</td>";
                                    echo "<td>" . $trip['source'] . "</td>";
                                    echo "<td>" . $trip['pickup'] . "</td>";
                                    echo "<td>" . $trip['dropoff'] . "</td>";
                                    echo "<td>" . $trip['rate'] . "</td>";
                                    echo "<td>" . date("h:i A", strtotime($trip['call_time'])) . "</td>";
                                    echo "<td><button class='update-btn' data-id='" . $trip['cs_id'] . "'>Update</button></td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='11'>No pending trips found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="#" class="prev">Previous</a>
                    <a href="#" class="page-num active">1</a>
                    <a href="#" class="page-num">2</a>
                    <a href="#" class="page-num">3</a>
                    <a href="#" class="next">Next</a>
                </div>

            </div>
        </div>
    </div>

    <div class="modal-overlay" id="updateTripModal" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Update Trip</h3>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="updateTripForm" method="post">
                    <input type="hidden" id="update_id" name="cs_id">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_topsheet">Topsheet No:</label>
                            <input type="text" id="update_topsheet" name="topsheet" readonly class="disabled-input">
                        </div>
                        <div class="form-group">
                            <label for="update_waybill">Waybill No:</label>
                            <input type="number" id="update_waybill" name="waybill" required>
                        </div>
                        <div class="form-group">
                            <label for="update_date">Date:</label>
                            <input type="date" id="update_date" name="date" required>
                        </div>
                    </div>

                    <!-- Rest of the form remains unchanged -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_status">FO/PO/STO:</label>
                            <select id="update_status" name="status" required>
                                <option value="">Select Type</option>
                                <option value="Freight Order">Freight Order</option>
                                <option value="Purchase Order">Purchase Order</option>
                                <option value="Stock Transfer Order">Stock Transfer Order</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="update_delivery_type">Delivery Type:</label>
                            <input type="text" id="update_delivery_type" name="delivery_type" required>
                        </div>
                        <div class="form-group">
                            <label for="update_amount">Amount:</label>
                            <input type="number" id="update_amount" name="amount" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_source">Source:</label>
                            <input type="text" id="update_source" name="source" required>
                        </div>
                        <div class="form-group">
                            <label for="update_pickup">Pick Up:</label>
                            <input type="text" id="update_pickup" name="pickup" required>
                        </div>
                        <div class="form-group">
                            <label for="update_dropoff">Drop Off:</label>
                            <input type="text" id="update_dropoff" name="dropoff" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="update_rate">Rate:</label>
                            <input type="text" id="update_rate" name="rate" required>
                        </div>
                        <div class="form-group">
                            <label for="update_call_time">Call Time:</label>
                            <input type="time" id="update_call_time" name="call_time" required>
                        </div>
                        <div class="form-group">
                            <label style="visibility: hidden;">Placeholder</label>
                            <input type="hidden">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2"></div>
                        <div class="form-group" style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="submit" class="submit-btn">Update Trip</button>
                            <button type="button" class="delete-btn" id="deleteTrip">Delete</button>
                            <button type="button" class="cancel-btn">Cancel</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="addTripModal" style="display: none;">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Add Trip</h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addTripForm" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>Topsheet No: <span class="auto-generate-text">(Auto-generated)</span></label>
                        <input type="text" disabled placeholder="TS-XXXXX" class="disabled-input">
                    </div>
                    <div class="form-group">
                        <label for="waybill">Waybill No:</label>
                        <input type="number" id="waybill" name="waybill" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>

                <!-- Rest of the form remains unchanged -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">FO/PO/STO:</label>
                        <select id="status" name="status" required>
                            <option value="">Select Type</option>
                            <option value="Freight Order">Freight Order</option>
                            <option value="Purchase Order">Purchase Order</option>
                            <option value="Stock Transfer Order">Stock Transfer Order</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="delivery_type">Delivery Type:</label>
                        <input type="text" id="delivery_type" name="delivery_type" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount:</label>
                        <input type="number" id="amount" name="amount" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="source">Source:</label>
                        <input type="text" id="source" name="source" required>
                    </div>
                    <div class="form-group">
                        <label for="pickup">Pick Up:</label>
                        <input type="text" id="pickup" name="pickup" required>
                    </div>
                    <div class="form-group">
                        <label for="dropoff">Drop Off:</label>
                        <input type="text" id="dropoff" name="dropoff" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="rate">Rate:</label>
                        <input type="text" id="rate" name="rate" required>
                    </div>
                    <div class="form-group">
                        <label for="call_time">Call Time:</label>
                        <input type="time" id="call_time" name="call_time" required>
                    </div>
                    <div class="form-group">
                        <label style="visibility: hidden;">Placeholder</label>
                        <input type="hidden">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2"></div>
                    <div class="form-group" style="display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="submit" class="submit-btn">Add Trip</button>
                        <button type="button" class="cancel-btn">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.mobile-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const mainContent = document.querySelector('.main-content');

            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });

            function checkMobile() {
                if (window.innerWidth <= 768) {
                    mainContent.style.marginLeft = '0';
                    sidebar.classList.remove('active');
                } else {
                    mainContent.style.marginLeft = '-20px';
                }
            }

            checkMobile();
            window.addEventListener('resize', checkMobile);

            document.querySelectorAll('.metric-section').forEach(section => {
                section.addEventListener('click', function(event) {
                    event.preventDefault();
                    let link = this.getAttribute('data-href');
                    if (link) {
                        document.getElementById("loading-screen").style.display = "flex";
                        setTimeout(() => {
                            window.location.href = link;
                        }, 2000);
                    }
                });
            });

            const menuItems = document.querySelectorAll('.menu-item');

            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    const href = this.getAttribute('href');
                    const dataHref = this.getAttribute('data-href');

                    const ripple = document.createElement('div');
                    ripple.classList.add('ripple');

                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = `${size}px`;

                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.left = `${x}px`;
                    ripple.style.top = `${y}px`;

                    this.appendChild(ripple);

                    if (href !== '#') {
                        e.preventDefault();
                        document.getElementById("loading-screen").style.display = "flex";
                        setTimeout(() => {
                            ripple.remove();
                            window.location.href = href;
                        }, 2000);
                    } else {
                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    }
                });
            });

            const logoutLink = document.getElementById('logout-link');

            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    e.preventDefault();

                    const logoutButton = document.querySelector('.logout-section');
                    logoutButton.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';

                    document.getElementById("loading-screen").style.display = "flex";

                    setTimeout(() => {
                        logoutButton.style.backgroundColor = '';
                        window.location.href = this.getAttribute('href');
                    }, 2000);
                });
            }

            const pieSlice = document.querySelector('.pie-slice');
            setTimeout(() => {
                pieSlice.style.transition = 'transform 1s ease-out';
                pieSlice.style.transform = 'rotate(135deg)';
            }, 500);

            const bars = document.querySelectorAll('.bar');
            bars.forEach((bar, index) => {
                const heights = ['30%', '70%', '50%'];
                bar.style.height = '0';
                setTimeout(() => {
                    bar.style.transition = 'height 1s ease-out';
                    bar.style.height = heights[index % 3];
                }, 300 + (index * 100));
            });

            // When the Add Trip form is being opened, we need to show a placeholder for the topsheet
document.querySelector('.add-trip-btn').addEventListener('click', function() {
    // Reset the form first
    document.getElementById('addTripForm').reset();
    
    // Update the placeholder text for the topsheet field
    const topsheetInput = document.querySelector('#addTripModal .disabled-input');
    topsheetInput.placeholder = "TS-#####";
    
    // Show the modal
    document.getElementById('addTripModal').style.display = 'flex';
});

// Enhance the add trip form submission
document.getElementById('addTripForm').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById("loading-screen").style.display = "flex";
    const formData = new FormData(this);

    fetch('add_trip.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON response:', text);
            throw new Error('Invalid server response');
        }
    })
    .then(data => {
        document.getElementById("loading-screen").style.display = "none";
        if (data.success) {
            // Show the generated topsheet in the success message
            alert('Trip added successfully! Topsheet: ' + data.topsheet);
            document.getElementById('addTripModal').style.display = 'none';
            this.reset();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        document.getElementById("loading-screen").style.display = "none";
        console.error('Error details:', error);
        alert('An error occurred: ' + error.message);
    });
});

            const updateModal = document.getElementById('updateTripModal');
            const updateForm = document.getElementById('updateTripForm');
            const updateCloseBtn = updateModal.querySelector('.close-modal');
            const updateCancelBtn = updateModal.querySelector('.cancel-btn');

            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('update-btn')) {
                    const tripId = e.target.getAttribute('data-id');
                    fetchTripDetails(tripId);
                }
            });

            // Improved error handling for fetching trip details
            function fetchTripDetails(id) {
    document.getElementById("loading-screen").style.display = "flex";

    fetch('update_trip.php?cs_id=' + id)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON response:', text);
                throw new Error('Invalid server response');
            }
        })
        .then(data => {
            document.getElementById("loading-screen").style.display = "none";

            if (data.success) {
                // Fill in all form fields with existing data
                document.getElementById('update_id').value = data.data.cs_id;
                document.getElementById('update_topsheet').value = data.data.topsheet || '';
                document.getElementById('update_waybill').value = data.data.waybill || '';
                document.getElementById('update_date').value = data.data.date || '';
                document.getElementById('update_status').value = data.data.status || '';
                document.getElementById('update_delivery_type').value = data.data.delivery_type || '';
                document.getElementById('update_amount').value = data.data.amount || '';
                document.getElementById('update_source').value = data.data.source || '';
                document.getElementById('update_pickup').value = data.data.pickup || '';
                document.getElementById('update_dropoff').value = data.data.dropoff || '';
                document.getElementById('update_rate').value = data.data.rate || '';
                document.getElementById('update_call_time').value = data.data.call_time || '';
                
                // If you have truck/driver/helper fields in your form:
                if (document.getElementById('update_truck_id')) {
                    document.getElementById('update_truck_id').value = data.data.truck_id || '';
                }
                if (document.getElementById('update_driver_name')) {
                    document.getElementById('update_driver_name').value = data.data.driver || '';
                }
                if (document.getElementById('update_helper1_name')) {
                    document.getElementById('update_helper1_name').value = data.data.helper1 || '';
                }
                if (document.getElementById('update_helper2_name')) {
                    document.getElementById('update_helper2_name').value = data.data.helper2 || '';
                }

                updateModal.style.display = 'flex';
            } else {
                alert('Error: ' + (data.message || 'Failed to fetch trip details'));
            }
        })
        .catch(error => {
            document.getElementById("loading-screen").style.display = "none";
            console.error('Error details:', error);
            alert('An error occurred: ' + error.message);
        });
}

// Function to submit the update form
function submitUpdateForm() {
    document.getElementById("loading-screen").style.display = "flex";
    
    const form = document.getElementById('update-trip-form');
    const formData = new FormData(form);
    
    fetch('update_trip.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById("loading-screen").style.display = "none";
        
        if (data.success) {
            alert('Trip updated successfully');
            updateModal.style.display = 'none';
            // Reload the page or refresh the data as needed
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update trip'));
        }
    })
    .catch(error => {
        document.getElementById("loading-screen").style.display = "none";
        console.error('Error:', error);
        alert('An error occurred while updating the trip');
    });
    
    return false; // Prevent form submission
}

            updateCloseBtn.addEventListener('click', function() {
                updateModal.style.display = 'none';
            });

            updateCancelBtn.addEventListener('click', function() {
                updateModal.style.display = 'none';
                updateForm.reset();
            });

            window.addEventListener('click', function(event) {
                if (event.target === updateModal) {
                    updateModal.style.display = 'none';
                }
            });

            // Improved error handling for update trip
            updateForm.addEventListener('submit', function(e) {
                e.preventDefault();
                document.getElementById("loading-screen").style.display = "flex";
                const formData = new FormData(updateForm);

                fetch('update_trip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON response:', text);
                        throw new Error('Invalid server response');
                    }
                })
                .then(data => {
                    document.getElementById("loading-screen").style.display = "none";
                    if (data.success) {
                        alert('Trip updated successfully!');
                        updateModal.style.display = 'none';
                        updateForm.reset();
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    document.getElementById("loading-screen").style.display = "none";
                    console.error('Error details:', error);
                    alert('An error occurred: ' + error.message);
                });
            });
        });
        // Add this to your existing JavaScript file
document.getElementById('deleteTrip').addEventListener('click', function() {
    const tripId = document.getElementById('update_id').value;
    
    if (confirm('Are you sure you want to delete this trip? This action cannot be undone.')) {
        // Send AJAX request to delete the trip
        fetch('delete_trip.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'cs_id=' + encodeURIComponent(tripId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Trip deleted successfully');
                // Close the modal
                document.getElementById('updateTripModal').style.display = 'none';
                // Refresh the page or update the trips list
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the trip');
        });
    }
});

// Get the add trip modal elements
const addTripModal = document.getElementById('addTripModal');
const addTripForm = document.getElementById('addTripForm');
const addTripCloseBtn = addTripModal.querySelector('.close-modal');
const addTripCancelBtn = addTripModal.querySelector('.cancel-btn');

// Close the add trip modal when the X button is clicked
addTripCloseBtn.addEventListener('click', function() {
    addTripModal.style.display = 'none';
});

// Close the add trip modal when the Cancel button is clicked
addTripCancelBtn.addEventListener('click', function() {
    addTripModal.style.display = 'none';
    addTripForm.reset();
});

// Close the add trip modal when clicking outside of it
window.addEventListener('click', function(event) {
    if (event.target === addTripModal) {
        addTripModal.style.display = 'none';
    }
});
    </script>
</body>
</html>