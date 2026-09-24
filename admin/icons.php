<?php
/**
 * Inline SVG icon set for the admin panel.
 * Usage: <?= ic('grid') ?>  or with a custom class: <?= ic('grid','icon icon-lg') ?>
 * All icons are 24x24, stroke-based, and inherit color via currentColor.
 */
function ic($name, $class = 'icon') {
    $paths = [
        'grid'        => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users'       => '<circle cx="9" cy="8" r="3.25"/><path d="M3.5 20c.6-3.4 3-5.4 5.5-5.4s4.9 2 5.5 5.4"/><circle cx="17" cy="8.5" r="2.5"/><path d="M15.2 14.8c2.1.2 3.9 2.1 4.4 5"/>',
        'clipboard'   => '<rect x="6" y="4.5" width="12" height="16" rx="1.75"/><path d="M9 4.5V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v.5"/><path d="M9 11h6"/><path d="M9 15h6"/><path d="M9 19h3.5"/>',
        'fingerprint' => '<path d="M12 3.2c-4.6 0-8.3 3.7-8.3 8.3 0 1.7.2 3.2.6 4.5"/><path d="M12 3.2c4.6 0 8.3 3.7 8.3 8.3 0 1.1-.05 2.1-.15 3"/><path d="M7.6 20.4c-.6-1.1-1-2.3-1.3-3.6-.3-1.4-.5-2.8-.5-4.3a6.2 6.2 0 0 1 12.4 0c0 .8-.05 1.6-.15 2.3"/><path d="M9.7 20.9c-.9-2.1-1.5-4.4-1.5-6.9a3.8 3.8 0 0 1 7.6 0c0 .5 0 1-.05 1.5"/><path d="M12 22c-.7-1.5-1.2-3-1.4-4.6a1.4 1.4 0 0 1 2.8-.3c.1.9.35 1.75.7 2.5"/>',
        'logout'      => '<path d="M9 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5A2.25 2.25 0 0 0 6.75 19.5H9"/><path d="M15 16.5l4.5-4.5-4.5-4.5"/><path d="M19.25 12h-10"/>',
        'search'      => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.8-4.8"/>',
        'plus'        => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'edit'        => '<path d="M4 20h4.2L18.6 9.6a2.4 2.4 0 0 0 0-3.4l-.8-.8a2.4 2.4 0 0 0-3.4 0L4 15.8V20Z"/><path d="M13 6.8l4.2 4.2"/>',
        'trash'       => '<path d="M5 7h14"/><path d="M9.5 7V5.2A1.2 1.2 0 0 1 10.7 4h2.6a1.2 1.2 0 0 1 1.2 1.2V7"/><path d="M7 7l.8 12.1A2 2 0 0 0 9.8 21h4.4a2 2 0 0 0 2-1.9L17 7"/><path d="M10.2 11v6"/><path d="M13.8 11v6"/>',
        'calendar'    => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3.5 10h17"/>',
        'reset'       => '<path d="M4.2 12a7.8 7.8 0 1 1 2.3 5.5"/><path d="M4.2 17.8V13h4.8"/>',
        'save'        => '<path d="M5 4.5h11.2L19 7.3V19a.7.7 0 0 1-.7.7H5a.7.7 0 0 1-.7-.7V5.2a.7.7 0 0 1 .7-.7Z"/><path d="M8 4.5V9h6.5V4.5"/><path d="M8 14h8v5.7H8Z"/>',
        'close'       => '<path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
        'check'       => '<path d="M5 12.5l4.5 4.5L19.5 7"/>',
        'alert'       => '<path d="M12 3.5 21 19H3Z"/><path d="M12 9.5v4.2"/><path d="M12 16.6v.1"/>',
        'phone'       => '<path d="M8 3.5H6.2A2.2 2.2 0 0 0 4 5.7v.5c0 8 6.8 14.8 14.8 14.8h.5a2.2 2.2 0 0 0 2.2-2.2V17l-4.6-1.7-1.6 1.9a12.6 12.6 0 0 1-6.6-6.6l1.9-1.6L8 3.5Z"/>',
        'key'         => '<circle cx="8" cy="15" r="4"/><path d="M11 12l9-9"/><path d="M16 7l3 3"/><path d="M13 10l2.5 2.5"/>',
        'history'     => '<path d="M4 12a8 8 0 1 1 2.6 5.9"/><path d="M4 20v-5h5"/><path d="M12 8v4.5l3 2"/>',
        'send'        => '<path d="M20.5 3.5 3 10.2l6.4 2.4 2.4 6.4Z"/><path d="M20.5 3.5 11.8 12.2"/>',
        'sliders'     => '<path d="M4 7h8"/><circle cx="15" cy="7" r="2"/><path d="M17 7h3"/><path d="M4 12h3"/><circle cx="10" cy="12" r="2"/><path d="M12 12h8"/><path d="M4 17h11"/><circle cx="17" cy="17" r="2"/><path d="M19 17h1"/>',
        'chart-bar'   => '<path d="M4 20V13"/><path d="M10.5 20V8"/><path d="M17 20V5"/><path d="M3 20h18"/>',
        'signal'      => '<path d="M4 19.5V15"/><path d="M9.3 19.5V11"/><path d="M14.7 19.5V7"/><path d="M20 19.5V3.5"/>',
        'download'    => '<path d="M12 4v11"/><path d="M7.5 11 12 15.5 16.5 11"/><path d="M5 19h14"/>',
        'print'       => '<path d="M7 8V4h10v4"/><rect x="5" y="8" width="14" height="7" rx="1.2"/><path d="M7 15h10v5H7Z"/>',
        'sun'         => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2.2"/><path d="M12 19.3v2.2"/><path d="M4.2 4.2l1.6 1.6"/><path d="M18.2 18.2l1.6 1.6"/><path d="M2.5 12h2.2"/><path d="M19.3 12h2.2"/><path d="M4.2 19.8l1.6-1.6"/><path d="M18.2 5.8l1.6-1.6"/>',
    ];
    $body = $paths[$name] ?? $paths['grid'];
    return '<svg class="'.htmlspecialchars($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$body.'</svg>';
}
