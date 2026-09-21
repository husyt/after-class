<?php
// ============================================
// SHARED AVATAR HELPER
// ============================================
if (!function_exists('get_avatar_url')) {
    function get_avatar_url($profile_picture) {
        $avatar_seeds = ['Felix', 'Aneka', 'Leo', 'Mia', 'Kai', 'Zara', 'Ravi', 'Nora'];
        if (empty($profile_picture)) return null;
        $num = (int) preg_replace('/\D/', '', $profile_picture);
        if ($num >= 1 && $num <= 8) {
            $seed = $avatar_seeds[$num - 1];
            return "https://api.dicebear.com/7.x/adventurer/svg?seed={$seed}&size=200&backgroundColor=7c3aed,d13639,f97316,2ecc71";
        }
        return null;
    }
}

if (!function_exists('render_nav_avatar')) {
    function render_nav_avatar($profile_picture) {
        $url = get_avatar_url($profile_picture);
        if ($url): ?>
            <img src="<?= htmlspecialchars($url) ?>" 
                 alt="Profile" 
                 style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;"
                 onerror="this.style.display='none';this.parentElement.innerHTML='<svg width=\'22\' height=\'22\' viewBox=\'0 0 24 24\' fill=\'currentColor\'><circle cx=\'12\' cy=\'8\' r=\'4\'/><path d=\'M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2\'/></svg>'">
        <?php else: ?>
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4"/>
                <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
            </svg>
        <?php endif;
    }
}