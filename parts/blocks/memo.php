<?php

/**
 * Memo Block Template.
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
$id = 'memo-' . $block['id'];
if (!empty($block['anchor'])) {
    $id = $block['anchor'];
}

// Create class attribute allowing for custom "className" and "align" values.
$className = 'memo';
if (!empty($block['className'])) {
    $className .= ' ' . $block['className'];
}
if (!empty($block['align'])) {
    $className .= ' align' . $block['align'];
}

// Load values and handle defaults.
?>
<div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">

<!-- wp:group {"metadata":{"name":"Memo"},"style":{"color":{"background":"#e9fa70"},"elements":{"link":{"color":{"text":"var:preset|color|accent-4"}}},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"textColor":"accent-4","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-accent-4-color has-text-color has-background has-link-color" style="background-color:#e9fa70;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--60)">

    <!-- wp:heading -->
    <h2 class="wp-block-heading"><?php echo esc_html(get_field('titre_memo') ?: 'Memo'); ?></h2>
    <!-- /wp:heading -->

    <!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
    <ul class="wp-block-group memo-list">

        <?php if (have_rows('vocabulaire')): ?>
            <?php while (have_rows('vocabulaire')): the_row();
                $english_term = get_sub_field('english_word');
                $french_translation = get_sub_field('traduction');
                
                // Affiche seulement si les deux champs sont remplis
                if (!empty($english_term) && !empty($french_translation)): ?>
                    <!-- wp:paragraph -->
                    <li class="memo-item">
                        <span style="font-weight: bold;">
                            <?php echo esc_html($english_term); ?>
                        </span> : 
                        <?php echo esc_html($french_translation); ?>
                    </li>
                    <!-- /wp:paragraph -->
                <?php endif; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Contenu par défaut si pas de vocabulaire -->
            <!-- wp:paragraph -->
            <p><span style="text-decoration: underline;">to walk</span> : marcher</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph -->
            <p><span style="text-decoration: underline;">to look after</span> : surveiller</p>
            <!-- /wp:paragraph -->
        <?php endif; ?>

    </ul>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->