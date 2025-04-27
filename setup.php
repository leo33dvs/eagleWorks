<?php
// Setup script for EagleWorks
// This script creates necessary directories and default files

// Define necessary directories
$directories = [
    'assets/uploads',
    'assets/uploads/profile_pictures',
    'assets/uploads/company_logos',
    'assets/uploads/resumes'
];

// Create each directory if it doesn't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: $dir<br>";
        } else {
            echo "Failed to create directory: $dir<br>";
        }
    } else {
        echo "Directory already exists: $dir<br>";
    }
}

// Copy default images if they don't exist
$defaultImages = [
    ['source' => 'https://via.placeholder.com/150?text=Default+Profile', 'destination' => 'assets/uploads/profile_pictures/default-profile.jpg'],
    ['source' => 'https://via.placeholder.com/150?text=Default+Company', 'destination' => 'assets/uploads/company_logos/default-company.jpg']
];

foreach ($defaultImages as $image) {
    if (!file_exists($image['destination'])) {
        if (copy($image['source'], $image['destination'])) {
            echo "Created default image: {$image['destination']}<br>";
        } else {
            echo "Failed to create default image: {$image['destination']}<br>";
        }
    } else {
        echo "Default image already exists: {$image['destination']}<br>";
    }
}

echo "<h2>Setup completed!</h2>";
echo "<p>You can now <a href='index.php'>visit the homepage</a>.</p>";
?>