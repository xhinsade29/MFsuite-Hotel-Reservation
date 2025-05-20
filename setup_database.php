<?php
include('functions/db_connect.php');

// SQL commands to create and populate tables
$sql_commands = [
    // Create room types table
    "CREATE TABLE IF NOT EXISTS tbl_room_type (
        type_id INT PRIMARY KEY AUTO_INCREMENT,
        type_name VARCHAR(100) NOT NULL,
        description TEXT,
        max_occupancy INT NOT NULL,
        room_price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Create services table
    "CREATE TABLE IF NOT EXISTS tbl_services (
        service_id INT PRIMARY KEY AUTO_INCREMENT,
        service_name VARCHAR(100) NOT NULL,
        service_description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    // Create room services junction table
    "CREATE TABLE IF NOT EXISTS tbl_room_services (
        room_type_id INT,
        service_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (room_type_id, service_id),
        FOREIGN KEY (room_type_id) REFERENCES tbl_room_type(type_id) ON DELETE CASCADE,
        FOREIGN KEY (service_id) REFERENCES tbl_services(service_id) ON DELETE CASCADE
    )"
];

// Execute each SQL command
foreach ($sql_commands as $sql) {
    if (!mysqli_query($mycon, $sql)) {
        die("Error creating table: " . mysqli_error($mycon));
    }
}

// Insert sample data only if tables are empty
$check_rooms = mysqli_query($mycon, "SELECT COUNT(*) as count FROM tbl_room_type");
$room_count = mysqli_fetch_assoc($check_rooms)['count'];

if ($room_count == 0) {
    // Insert sample room types
    $room_types = [
        "('Deluxe Room', 'Spacious comfort with modern amenities', 2, 2500.00)",
        "('Executive Suite', 'Luxury living with premium services', 3, 3500.00)",
        "('Presidential Suite', 'Ultimate luxury and exclusivity', 4, 5000.00)"
    ];
    
    $sql = "INSERT INTO tbl_room_type (type_name, description, max_occupancy, room_price) VALUES " . implode(", ", $room_types);
    if (!mysqli_query($mycon, $sql)) {
        die("Error inserting room types: " . mysqli_error($mycon));
    }

    // Insert sample services
    $services = [
        "('Free WiFi', 'High-speed internet access throughout your stay')",
        "('Room Service', '24/7 room service available')",
        "('Mini Bar', 'Well-stocked mini bar with refreshments')",
        "('Air Conditioning', 'Individual climate control')",
        "('Flat-screen TV', '55-inch smart TV with cable channels')",
        "('Coffee Maker', 'In-room coffee and tea facilities')",
        "('Safe', 'In-room safe for valuables')",
        "('Daily Housekeeping', 'Professional cleaning service')",
        "('Laundry Service', 'Same-day laundry and dry cleaning')",
        "('Breakfast Buffet', 'Complimentary breakfast buffet')"
    ];
    
    $sql = "INSERT INTO tbl_services (service_name, service_description) VALUES " . implode(", ", $services);
    if (!mysqli_query($mycon, $sql)) {
        die("Error inserting services: " . mysqli_error($mycon));
    }

    // Link services to room types
    $room_services = [
        // Deluxe Room services
        "(1, 1), (1, 2), (1, 3), (1, 4), (1, 5)",
        // Executive Suite services
        "(2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7)",
        // Presidential Suite services
        "(3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10)"
    ];
    
    $sql = "INSERT INTO tbl_room_services (room_type_id, service_id) VALUES " . implode(", ", $room_services);
    if (!mysqli_query($mycon, $sql)) {
        die("Error linking services to rooms: " . mysqli_error($mycon));
    }
}

echo "Database setup completed successfully!";
?> 