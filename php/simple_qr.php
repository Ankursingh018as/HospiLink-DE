<?php
/**
 * Simple QR Code Generator using external API
 * Fallback to text-based display if API unavailable
 */

class SimpleQRCode {
    
    public static function generate($data, $size = 300) {
        // Use reliable QR code API service
        // Multiple fallback options
        
        $apis = [
            "https://quickchart.io/qr?text=" . urlencode($data) . "&size=" . $size,
            "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data),
            "https://chart.googleapis.com/chart?cht=qr&chs={$size}x{$size}&chl=" . urlencode($data)
        ];
        
        // Return first API URL (browser will try to load it)
        return $apis[0];
    }
    
    private static function drawFinderPattern($x, $y, $cellSize) {
        $size = $cellSize * 7;
        $svg = '';
        
        // Outer black square
        $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $size . '" height="' . $size . '" fill="black"/>';
        
        // White border
        $innerOffset = $cellSize;
        $innerSize = $size - ($cellSize * 2);
        $svg .= '<rect x="' . ($x + $innerOffset) . '" y="' . ($y + $innerOffset) . '" width="' . $innerSize . '" height="' . $innerSize . '" fill="white"/>';
        
        // Center black square
        $centerOffset = $cellSize * 2;
        $centerSize = $cellSize * 3;
        $svg .= '<rect x="' . ($x + $centerOffset) . '" y="' . ($y + $centerOffset) . '" width="' . $centerSize . '" height="' . $centerSize . '" fill="black"/>';
        
        return $svg;
    }
    
    private static function isFinderArea($x, $y, $gridSize) {
        // Top-left
        if ($x < 9 && $y < 9) return true;
        // Top-right
        if ($x >= $gridSize - 9 && $y < 9) return true;
        // Bottom-left
        if ($x < 9 && $y >= $gridSize - 9) return true;
        
        return false;
    }
}
?>
