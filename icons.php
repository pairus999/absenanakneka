<?php
/**
 * Inline SVG icon set untuk SITUS PUBLIK (root folder: index.php, siswa.php, absensi.php).
 * JANGAN dipakai untuk folder admin/ — folder admin punya icons.php sendiri yang isinya beda.
 *
 * Cara pakai: rename file ini jadi icons.php, taruh di folder root
 * (satu folder dengan config.php, index.php, siswa.php, absensi.php).
 *
 * Usage di dalam PHP: <?= ic('home') ?>
 */
function ic($name, $class = 'icon') {
    $paths = [
        'home'        => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6 10v9.5a.8.8 0 0 0 .8.8H9.5v-6h5v6H17.2a.8.8 0 0 0 .8-.8V10"/>',
        'users'       => '<circle cx="9" cy="8" r="3.25"/><path d="M3.5 20c.6-3.4 3-5.4 5.5-5.4s4.9 2 5.5 5.4"/><circle cx="17" cy="8.5" r="2.5"/><path d="M15.2 14.8c2.1.2 3.9 2.1 4.4 5"/>',
        'clipboard'   => '<rect x="6" y="4.5" width="12" height="16" rx="1.75"/><path d="M9 4.5V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v.5"/><path d="M9 11h6"/><path d="M9 15h6"/><path d="M9 19h3.5"/>',
        'search'      => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.8-4.8"/>',
        'clock'       => '<circle cx="12" cy="12" r="8.25"/><path d="M12 7.5V12l3 2"/>',
        'user'        => '<circle cx="12" cy="8.5" r="3.5"/><path d="M5 20c.9-4 3.6-6.2 7-6.2s6.1 2.2 7 6.2"/>',
        'arrow-right' => '<path d="M4.5 12h14.2"/><path d="M13.5 6.5 19 12l-5.5 5.5"/>',
        'save'        => '<path d="M5 4.5h11.2L19 7.3V19a.7.7 0 0 1-.7.7H5a.7.7 0 0 1-.7-.7V5.2a.7.7 0 0 1 .7-.7Z"/><path d="M8 4.5V9h6.5V4.5"/><path d="M8 14h8v5.7H8Z"/>',
        'alert'       => '<path d="M12 3.5 21 19H3Z"/><path d="M12 9.5v4.2"/><path d="M12 16.6v.1"/>',
        'signal'      => '<path d="M4 19.5V14"/><path d="M9.5 19.5V10"/><path d="M15 19.5V6.5"/><path d="M20.5 19.5V3.5"/>',
        'inbox'       => '<path d="M4 12.5h4.3l1.4 2.6h4.6l1.4-2.6H20"/><rect x="4" y="12.5" width="16" height="7" rx="1.6"/><path d="M6.5 12.5 8.7 5h6.6l2.2 7.5"/>',
        'close'       => '<path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
        'check'       => '<path d="M5 12.5l4.5 4.5L19.5 7"/>',
    ];
    $body = $paths[$name] ?? $paths['home'];
    return '<svg class="'.htmlspecialchars($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$body.'</svg>';
}
