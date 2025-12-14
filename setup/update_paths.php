<?php
/**
 * Script to update file paths after folder reorganization
 * This updates all HTML/PHP files in the pages folder to use correct relative paths
 */

$pagesDir = __DIR__ . '/../pages/';
$files = glob($pagesDir . '*.{html,php}', GLOB_BRACE);

$replacements = [
    // CSS paths - add ../ prefix
    'href="css/' => 'href="../css/',
    'href=\'css/' => 'href=\'../css/',
    
    // Image paths - add ../ prefix  
    'src="images/' => 'src="../images/',
    'src=\'images/' => 'src=\'../images/',
    
    // Navigation links - update to relative paths for pages
    'href="index.html"' => 'href="../index.html"',
    'href="about.html"' => 'href="about.html"',
    'href="appointment.html"' => 'href="appointment.html"',
    'href="beds.html"' => 'href="beds.html"',
    'href="admit.html"' => 'href="admit.html"',
    'href="contact.html"' => 'href="contact.html"',
    'href="sign_new.html"' => 'href="sign_new.html"',
    'href="tc.html"' => 'href="tc.html"',
    'href="scan.php"' => 'href="scan.php"',
    
    // PHP paths
    'action="php/' => 'action="../php/',
    'action=\'php/' => 'action=\'../php/',
    'src="php/' => 'src="../php/"',
    'src=\'php/' => 'src=\'../php/\'',
    
    // Script paths
    'src="script.js"' => 'src="../scripts/script.js"',
    'src="sign.js"' => 'src="../scripts/sign.js"',
];

$updatedFiles = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $originalContent = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $updatedFiles[] = basename($file);
    }
}

echo "✓ Path updates complete!\n\n";
echo "Updated files:\n";
foreach ($updatedFiles as $file) {
    echo "  - $file\n";
}

if (empty($updatedFiles)) {
    echo "  No files needed updating\n";
}
?>
