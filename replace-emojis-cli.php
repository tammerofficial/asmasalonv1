<?php
/**
 * CLI Tool: Replace Emojis with CoreUI Icons in Vue.js Files
 * 
 * Usage:
 *   php replace-emojis-cli.php --scan          # Scan only (dry-run)
 *   php replace-emojis-cli.php --replace        # Replace emojis
 *   php replace-emojis-cli.php --file=path.vue # Process single file
 */

// Emoji to CoreUI Icon mapping
$emojiMap = [
    // Common UI Emojis
    '🏭' => 'cil-factory',
    '➕' => 'cil-plus',
    '✏️' => 'cil-pencil',
    '🗑️' => 'cil-trash',
    '📊' => 'cil-chart-line',
    '✅' => 'cil-check-circle',
    '⚠️' => 'cil-warning',
    '🔄' => 'cil-reload',
    '👤' => 'cil-user',
    '📋' => 'cil-clipboard',
    '🎯' => 'cil-target',
    '💾' => 'cil-save',
    '📦' => 'cil-box',
    '🔍' => 'cil-magnifying-glass',
    '⭐' => 'cil-star',
    '💡' => 'cil-lightbulb',
    '🚀' => 'cil-rocket',
    '💰' => 'cil-money',
    '📈' => 'cil-chart-line',
    '📉' => 'cil-chart-line',
    '🎨' => 'cil-paint',
    '🔧' => 'cil-settings',
    '⚙️' => 'cil-settings',
    '🔐' => 'cil-lock-locked',
    '🛡️' => 'cil-shield-alt',
    '📝' => 'cil-pencil',
    '📄' => 'cil-file',
    '📌' => 'cil-pin',
    '📍' => 'cil-location-pin',
    '🎁' => 'cil-gift',
    '🎉' => 'cil-bell',
    '🎊' => 'cil-bell',
    '🔥' => 'cil-fire',
    '💯' => 'cil-check-circle',
    '❌' => 'cil-x-circle',
    '⏰' => 'cil-clock',
    '📅' => 'cil-calendar',
    '📱' => 'cil-devices',
    '💻' => 'cil-laptop',
    '🖥️' => 'cil-monitor',
    '📧' => 'cil-envelope-letter',
    '📞' => 'cil-phone',
    '🔔' => 'cil-bell',
    '📢' => 'cil-bullhorn',
    '🎵' => 'cil-music-note',
    '🎬' => 'cil-media-play',
    '📸' => 'cil-camera',
    '🎥' => 'cil-video',
    '📺' => 'cil-tv',
    '📻' => 'cil-radio',
    '🔊' => 'cil-volume-high',
    '🔇' => 'cil-volume-off',
    '📡' => 'cil-satellite',
    '🌐' => 'cil-globe-alt',
    '🔗' => 'cil-link',
    '📎' => 'cil-paperclip',
    '✂️' => 'cil-scissors',
    '📏' => 'cil-ruler',
    '📐' => 'cil-ruler-pencil',
    '🔨' => 'cil-hammer',
    '⚡' => 'cil-bolt',
    '💎' => 'cil-diamond',
    '🏆' => 'cil-trophy',
    '🎖️' => 'cil-award',
    '🏅' => 'cil-medal',
    '🎗️' => 'cil-flag-alt',
    '🏁' => 'cil-flag-alt',
    '🚩' => 'cil-flag-alt',
    '📍' => 'cil-location-pin',
    '🗺️' => 'cil-map',
    '🧭' => 'cil-compass',
    '⛽' => 'cil-gas-station',
    '🚗' => 'cil-car-alt',
    '🚕' => 'cil-car-alt',
    '🚙' => 'cil-car-alt',
    '🚌' => 'cil-car-alt',
    '🚎' => 'cil-car-alt',
    '🏎️' => 'cil-car-alt',
    '🚓' => 'cil-car-alt',
    '🚑' => 'cil-car-alt',
    '🚒' => 'cil-car-alt',
    '🚐' => 'cil-car-alt',
    '🛻' => 'cil-car-alt',
    '🚚' => 'cil-car-alt',
    '🚛' => 'cil-car-alt',
    '🚜' => 'cil-car-alt',
    '🏍️' => 'cil-car-alt',
    '🛵' => 'cil-car-alt',
    '🚲' => 'cil-bike',
    '🛴' => 'cil-bike',
    '🛹' => 'cil-bike',
    '🛼' => 'cil-bike',
    '🚁' => 'cil-airplane',
    '✈️' => 'cil-airplane',
    '🛫' => 'cil-airplane',
    '🛬' => 'cil-airplane',
    '🛩️' => 'cil-airplane',
    '💺' => 'cil-airplane',
    '🚀' => 'cil-rocket',
    '🛸' => 'cil-rocket',
    '🚤' => 'cil-boat-alt',
    '⛵' => 'cil-boat-alt',
    '🛥️' => 'cil-boat-alt',
    '🛳️' => 'cil-boat-alt',
    '⛴️' => 'cil-boat-alt',
    '🚢' => 'cil-boat-alt',
    '⚓' => 'cil-anchor',
    '⛽' => 'cil-gas-station',
    '🚧' => 'cil-warning',
    '🚨' => 'cil-warning',
    '🚥' => 'cil-warning',
    '🚦' => 'cil-warning',
    '🛑' => 'cil-warning',
    '🚏' => 'cil-bus-alt',
    '🗿' => 'cil-monument',
    '🏛️' => 'cil-building',
    '🏗️' => 'cil-building',
    '🧱' => 'cil-building',
    '🏘️' => 'cil-building',
    '🏚️' => 'cil-building',
    '🏠' => 'cil-home',
    '🏡' => 'cil-home',
    '🏢' => 'cil-building',
    '🏣' => 'cil-building',
    '🏤' => 'cil-building',
    '🏥' => 'cil-hospital',
    '🏦' => 'cil-bank',
    '🏨' => 'cil-building',
    '🏩' => 'cil-building',
    '🏪' => 'cil-building',
    '🏫' => 'cil-building',
    '🏬' => 'cil-building',
    '🏭' => 'cil-factory',
    '🏯' => 'cil-building',
    '🏰' => 'cil-building',
    '💒' => 'cil-building',
    '🗼' => 'cil-building',
    '🗽' => 'cil-building',
    '⛪' => 'cil-building',
    '🕌' => 'cil-building',
    '🛕' => 'cil-building',
    '🕍' => 'cil-building',
    '⛩️' => 'cil-building',
    '🕋' => 'cil-building',
    '⛲' => 'cil-building',
    '⛺' => 'cil-building',
    '🌁' => 'cil-building',
    '🌃' => 'cil-building',
    '🏙️' => 'cil-building',
    '🌄' => 'cil-building',
    '🌅' => 'cil-building',
    '🌆' => 'cil-building',
    '🌇' => 'cil-building',
    '🌉' => 'cil-building',
    '♨️' => 'cil-building',
    '🎠' => 'cil-building',
    '🎡' => 'cil-building',
    '🎢' => 'cil-building',
    '💈' => 'cil-building',
    '🎪' => 'cil-building',
    '🚂' => 'cil-train',
    '🚃' => 'cil-train',
    '🚄' => 'cil-train',
    '🚅' => 'cil-train',
    '🚆' => 'cil-train',
    '🚇' => 'cil-train',
    '🚈' => 'cil-train',
    '🚉' => 'cil-train',
    '🚊' => 'cil-train',
    '🚝' => 'cil-train',
    '🚞' => 'cil-train',
    '🚟' => 'cil-train',
    '🚠' => 'cil-train',
    '🚡' => 'cil-train',
    '🛶' => 'cil-boat-alt',
    '⛵' => 'cil-boat-alt',
    '🛥️' => 'cil-boat-alt',
    '🛳️' => 'cil-boat-alt',
    '⛴️' => 'cil-boat-alt',
    '🚢' => 'cil-boat-alt',
    '⚓' => 'cil-anchor',
    '⛽' => 'cil-gas-station',
    '🚧' => 'cil-warning',
    '🚨' => 'cil-warning',
    '🚥' => 'cil-warning',
    '🚦' => 'cil-warning',
    '🛑' => 'cil-warning',
    '🚏' => 'cil-bus-alt',
    '🗿' => 'cil-monument',
    '🏛️' => 'cil-building',
    '🏗️' => 'cil-building',
    '🧱' => 'cil-building',
    '🏘️' => 'cil-building',
    '🏚️' => 'cil-building',
    '🏠' => 'cil-home',
    '🏡' => 'cil-home',
    '🏢' => 'cil-building',
    '🏣' => 'cil-building',
    '🏤' => 'cil-building',
    '🏥' => 'cil-hospital',
    '🏦' => 'cil-bank',
    '🏨' => 'cil-building',
    '🏩' => 'cil-building',
    '🏪' => 'cil-building',
    '🏫' => 'cil-building',
    '🏬' => 'cil-building',
    '🏭' => 'cil-factory',
    '🏯' => 'cil-building',
    '🏰' => 'cil-building',
    '💒' => 'cil-building',
    '🗼' => 'cil-building',
    '🗽' => 'cil-building',
    '⛪' => 'cil-building',
    '🕌' => 'cil-building',
    '🛕' => 'cil-building',
    '🕍' => 'cil-building',
    '⛩️' => 'cil-building',
    '🕋' => 'cil-building',
    '⛲' => 'cil-building',
    '⛺' => 'cil-building',
    '🌁' => 'cil-building',
    '🌃' => 'cil-building',
    '🏙️' => 'cil-building',
    '🌄' => 'cil-building',
    '🌅' => 'cil-building',
    '🌆' => 'cil-building',
    '🌇' => 'cil-building',
    '🌉' => 'cil-building',
    '♨️' => 'cil-building',
    '🎠' => 'cil-building',
    '🎡' => 'cil-building',
    '🎢' => 'cil-building',
    '💈' => 'cil-building',
    '🎪' => 'cil-building',
];

// Get command line arguments
$options = getopt('', ['scan', 'replace', 'file:', 'help']);

// Show help
if (isset($options['help']) || (empty($options))) {
    echo "🔧 Emoji to CoreUI Icon Replacement Tool\n";
    echo "========================================\n\n";
    echo "Usage:\n";
    echo "  php replace-emojis-cli.php --scan              Scan files and show emojis found (dry-run)\n";
    echo "  php replace-emojis-cli.php --replace            Replace emojis with CoreUI icons\n";
    echo "  php replace-emojis-cli.php --file=path.vue      Process single file\n";
    echo "  php replace-emojis-cli.php --help               Show this help\n\n";
    echo "Examples:\n";
    echo "  php replace-emojis-cli.php --scan\n";
    echo "  php replace-emojis-cli.php --replace\n";
    echo "  php replace-emojis-cli.php --file=assets/src/views/Dashboard.vue --replace\n\n";
    exit(0);
}

// Get plugin directory
$pluginDir = __DIR__;
$vueDir = $pluginDir . '/assets/src';

// Check if Vue directory exists
if (!is_dir($vueDir)) {
    echo "❌ Error: Vue directory not found: {$vueDir}\n";
    exit(1);
}

// Function to find all Vue files
function findVueFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'vue') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

// Function to replace emojis in content
function replaceEmojis($content, $emojiMap, $dryRun = false) {
    $replacements = [];
    $newContent = $content;
    
    foreach ($emojiMap as $emoji => $icon) {
        $count = mb_substr_count($newContent, $emoji);
        if ($count > 0) {
            $replacements[$emoji] = [
                'icon' => $icon,
                'count' => $count,
            ];
            
            if (!$dryRun) {
                // Pattern 1: In template text content (between tags)
                // Example: <p>✅ Text</p> -> <p><CIcon icon="cil-check-circle" class="me-1" /> Text</p>
                $pattern1 = '/(<[^>]+>)([^<]*?)' . preg_quote($emoji, '/') . '([^<]*?)(<\/[^>]+>)/u';
                $newContent = preg_replace_callback($pattern1, function($matches) use ($icon, $emoji) {
                    $before = $matches[1] . $matches[2];
                    $after = $matches[3] . $matches[4];
                    return $before . '<CIcon icon="' . $icon . '" class="me-1" />' . $after;
                }, $newContent);
                
                // Pattern 2: In template text (standalone or at start/end)
                // Example: ✅ Text or Text ✅
                $pattern2 = '/' . preg_quote($emoji, '/') . '/u';
                $replacement2 = '<CIcon icon="' . $icon . '" class="me-1" />';
                $newContent = preg_replace($pattern2, $replacement2, $newContent);
                
                // Pattern 3: In JavaScript strings (toast messages, etc.)
                // Example: toast.success('✅ Message') -> toast.success('<CIcon icon="cil-check-circle" class="me-1" /> Message')
                // But we need to be careful - only replace in template strings or simple strings
                // This is handled by pattern2 as well since Vue templates can contain JS
            }
        }
    }
    
    return [
        'content' => $newContent,
        'replacements' => $replacements,
        'changed' => !empty($replacements),
    ];
}

// Function to process a single file
function processFile($filePath, $emojiMap, $dryRun = false) {
    $relativePath = str_replace(__DIR__ . '/', '', $filePath);
    
    if (!file_exists($filePath)) {
        echo "❌ File not found: {$relativePath}\n";
        return false;
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "❌ Error reading file: {$relativePath}\n";
        return false;
    }
    
    $result = replaceEmojis($content, $emojiMap, $dryRun);
    
    if (!empty($result['replacements'])) {
        echo "📄 {$relativePath}\n";
        foreach ($result['replacements'] as $emoji => $info) {
            echo "   {$emoji} → cil-icon: {$info['icon']} (found {$info['count']} time(s))\n";
        }
        
            if (!$dryRun) {
                // Check if CIcon is already imported
                if (!preg_match('/import.*CIcon.*from.*@coreui\/icons-vue/i', $result['content'])) {
                    // Find script setup or script tag
                    if (preg_match('/<script\s+setup>/i', $result['content'])) {
                        // Check if there are already imports
                        if (preg_match('/<script\s+setup>\s*\n\s*import/i', $result['content'])) {
                            // Add import after first import statement
                            $result['content'] = preg_replace(
                                '/(<script\s+setup>\s*\n\s*import[^;]+;)/i',
                                "$1\nimport { CIcon } from '@coreui/icons-vue';",
                                $result['content'],
                                1
                            );
                        } else {
                            // Add import after script setup
                            $result['content'] = preg_replace(
                                '/(<script\s+setup>)/i',
                                "$1\nimport { CIcon } from '@coreui/icons-vue';",
                                $result['content'],
                                1
                            );
                        }
                    } elseif (preg_match('/<script>/i', $result['content'])) {
                        // Check if there are already imports
                        if (preg_match('/<script>\s*\n\s*import/i', $result['content'])) {
                            // Add import after first import statement
                            $result['content'] = preg_replace(
                                '/(<script>\s*\n\s*import[^;]+;)/i',
                                "$1\nimport { CIcon } from '@coreui/icons-vue';",
                                $result['content'],
                                1
                            );
                        } else {
                            // Add import after script tag
                            $result['content'] = preg_replace(
                                '/(<script>)/i',
                                "$1\nimport { CIcon } from '@coreui/icons-vue';",
                                $result['content'],
                                1
                            );
                        }
                    }
                }
            
            // Write file
            if (file_put_contents($filePath, $result['content']) === false) {
                echo "   ❌ Error writing file: {$relativePath}\n";
                return false;
            } else {
                echo "   ✅ File updated successfully\n";
            }
        }
        echo "\n";
    }
    
    return true;
}

// Main execution
$dryRun = isset($options['scan']) && !isset($options['replace']);
$singleFile = isset($options['file']);

if ($singleFile) {
    // Process single file
    $filePath = $options['file'];
    if (!is_file($filePath) && !is_file($pluginDir . '/' . $filePath)) {
        $filePath = $pluginDir . '/' . $filePath;
    }
    
    if (!is_file($filePath)) {
        echo "❌ File not found: {$filePath}\n";
        exit(1);
    }
    
    echo ($dryRun ? "🔍 Scanning" : "🔄 Replacing") . " emojis in: {$filePath}\n\n";
    processFile($filePath, $emojiMap, $dryRun);
} else {
    // Process all Vue files
    $vueFiles = findVueFiles($vueDir);
    
    if (empty($vueFiles)) {
        echo "⚠️  No Vue files found in: {$vueDir}\n";
        exit(0);
    }
    
    echo ($dryRun ? "🔍 Scanning" : "🔄 Replacing") . " emojis in " . count($vueFiles) . " Vue file(s)...\n\n";
    
    $processed = 0;
    $filesWithEmojis = 0;
    
    foreach ($vueFiles as $file) {
        if (processFile($file, $emojiMap, $dryRun)) {
            $processed++;
            $relativePath = str_replace($pluginDir . '/', '', $file);
            $content = file_get_contents($file);
            $result = replaceEmojis($content, $emojiMap, true);
            if (!empty($result['replacements'])) {
                $filesWithEmojis++;
            }
        }
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "📊 Summary:\n";
    echo "   Total files: " . count($vueFiles) . "\n";
    echo "   Files processed: {$processed}\n";
    echo "   Files with emojis: {$filesWithEmojis}\n";
    
    if ($dryRun) {
        echo "\n💡 Run with --replace to apply changes\n";
    } else {
        echo "\n✅ All files processed!\n";
        echo "💡 Don't forget to rebuild assets: cd assets && npm run build\n";
    }
}

