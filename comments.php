<?php
if (post_password_required()) {
    return;
}
// Ensure commenter info exists
$commenter = wp_get_current_commenter();
?>

<div id="comments" class="comments-area <?php echo get_post_type() === 'debate_topic' ? 'forum-comments' : ''; ?>">
    <?php if (have_comments()) : ?>
        <h3 class="comments-title">
            <?php
            $comment_count = get_comments_number();
            echo esc_html( sprintf(_n('%s comentario', '%s comentarios', $comment_count, 'lanota-theme-2026'), number_format_i18n($comment_count)) );
            ?>
        </h3>

        <ol class="comment-list">
            <?php
            $cb = function_exists('lanota_2026_comment_callback') ? 'lanota_2026_comment_callback' : null;
            wp_list_comments(array(
                'style' => 'ol',
                'short_ping' => true,
                'avatar_size' => 40,
                'reply_text' => __('Responder', 'lanota-theme-2026'),
                'callback' => $cb,
            ));
            ?>
        </ol>

        <?php if (get_comment_pages_count() > 1 && get_option('page_comments')) : ?>
            <nav class="comment-navigation">
                <div class="nav-previous"><?php previous_comments_link('← Comentarios anteriores'); ?></div>
                <div class="nav-next"><?php next_comments_link('Comentarios siguientes →'); ?></div>
            </nav>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>
        <p class="no-comments"><?php esc_html_e('Los comentarios están cerrados.', 'lanota-theme-2026'); ?></p>
    <?php endif; ?>

    <?php
    $is_debate = (get_post_type() === 'debate_topic');
    $can_comment_forum = is_user_logged_in() && current_user_can('nm_forum_comment');
    // If login is required to comment and user is not logged in, show a helpful CTA above the form
    if (!is_user_logged_in() && get_option('comment_registration')) {
        $login_url = function_exists('nm_get_login_url') ? nm_get_login_url( get_permalink() . '#respond' ) : wp_login_url( get_permalink() . '#respond' );
        echo '<div class="forum-login-cta">' . sprintf(
            wp_kses_post(__('Debes <a href="%s">iniciar sesión</a> para comentar.', 'lanota-theme-2026')),
            esc_url($login_url)
        ) . '</div>';
    }

    if (!$is_debate || $can_comment_forum) {
    comment_form(array(
        'title_reply' => __('Dejá un comentario', 'lanota-theme-2026'),
        'title_reply_to' => __('Responder a %s', 'lanota-theme-2026'),
        'cancel_reply_link' => __('Cancelar respuesta', 'lanota-theme-2026'),
        'class_form' => 'comment-form',
        'format' => 'html5',
        'label_submit' => __('Publicar comentario', 'lanota-theme-2026'),
        'class_submit' => 'btn btn-primary',
        'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" aria-describedby="comment-help" />',
        'comment_notes_after' => '<div id="comment-help" class="form-help">Tu comentario será moderado antes de publicarse</div>',
        'comment_field' => '<p class="comment-form-comment"><label for="comment" class="required">' . __('Comentario', 'lanota-theme-2026') . ' <span aria-label="campo requerido">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" maxlength="65525" required="required" placeholder="' . esc_attr__('Escribe tu comentario...', 'lanota-theme-2026') . '" aria-describedby="comment-field-help"></textarea><div id="comment-field-help" class="form-help">Máximo 65,525 caracteres</div></p>',
        'fields' => array(
            'author' => '<p class="comment-form-author"><label for="author">' . esc_html__('Nombre *', 'lanota-theme-2026') . '</label><input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" required /></p>',
            'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__('Email *', 'lanota-theme-2026') . '</label><input id="email" name="email" type="email" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" required /></p>',
            'url'    => '<p class="comment-form-url"><label for="url">' . esc_html__('Sitio web', 'lanota-theme-2026') . '</label><input id="url" name="url" type="url" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" /></p>',
        ),
    ));
    } else {
        echo '<div class="forum-login-cta">'
           . '<p>' . wp_kses_post(__('Necesitás una suscripción participante para comentar en En Debate.', 'lanota-theme-2026')) . '</p>'
           . '<p><a class="btn btn-primary" href="' . esc_url(home_url('/suscripcion/')) . '">' . esc_html__('Ver planes', 'lanota-theme-2026') . '</a> '
           . '<a class="btn" href="' . esc_url( function_exists('nm_get_login_url') ? nm_get_login_url( get_permalink() . '#respond' ) : wp_login_url( get_permalink() . '#respond' ) ) . '">' . esc_html__('Iniciar sesión', 'lanota-theme-2026') . '</a></p>'
           . '</div>';
    }
    ?>
</div>

<style>
.comments-area {
    margin-top: 30px;
}

.comments-title {
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}

.comment-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.comment {
    margin-bottom: 20px;
    padding: 15px;
    background-color: var(--surface-color);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.comment-author {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.comment-author img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    margin-right: 10px;
}

.comment-meta {
    font-size: 14px;
    color: var(--text-secondary);
}

.comment-content {
    margin: 10px 0;
    line-height: 1.6;
}

.comment-reply-link {
    font-size: 12px;
    color: var(--primary-color);
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
    border: 1px solid var(--primary-color);
    transition: all 0.2s ease;
}

.comment-reply-link:hover {
    background-color: var(--primary-color);
    color: white;
}

.children {
    margin-left: 30px;
    margin-top: 15px;
}

.comment-form {
    margin-top: 30px;
    padding: 20px;
    background-color: var(--surface-color);
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.comment-form h3 {
    margin-bottom: 15px;
}

.comment-form p {
    margin-bottom: 15px;
}

.comment-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.comment-form input,
.comment-form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background-color: var(--background-color);
    color: var(--text-color);
    font-family: inherit;
}

.comment-form input:focus,
.comment-form textarea:focus {
    outline: none;
    border-color: var(--primary-color);
}

.comment-form .form-submit {
    margin-top: 15px;
}

.comment-form #submit {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.2s ease;
}

.comment-form #submit:hover {
    background-color: var(--primary-hover);
}

.comment-navigation {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}

.comment-navigation a {
    color: var(--primary-color);
    text-decoration: none;
    padding: 8px 16px;
    border: 1px solid var(--primary-color);
    border-radius: 20px;
    transition: all 0.2s ease;
}

.comment-navigation a:hover {
    background-color: var(--primary-color);
    color: white;
}

.no-comments {
    text-align: center;
    color: var(--text-secondary);
    font-style: italic;
    padding: 20px;
}

@media (max-width: 768px) {
    .children {
        margin-left: 15px;
    }
    
    .comment-form {
        padding: 15px;
    }
}
</style>
