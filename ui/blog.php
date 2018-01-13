<?php

/** A bit of security for those who are too clever for their own good. */
defined( 'ABSPATH' ) or die();

function dark_matter_settings_init() {
	register_setting( 'dark_matter_blog', 'dark_matter_allow_logins' );

	add_settings_section( 'dark_matter_blog_section', 'Dark Matter Blog Settings', 'dark_matter_blog_section_title', 'dark_matter_blog' );

	add_settings_field( 'dark_matter_allow_logins', 'Allow Login on domains?', 'dark_matter_allow_logins_checkbox', 'dark_matter_blog', 'dark_matter_blog_section' );
}
add_action( 'admin_init', 'dark_matter_settings_init' );

function dark_matter_blog_section_title() { ?>
	<h2><?php _e( 'Blog Settings', 'dark-matter' ); ?></h2>
<?php }

function dark_matter_allow_logins_checkbox() { ?>
	<input name="dark_matter_allow_logins" type="checkbox" value="yes" <?php checked( 'yes', get_option( 'dark_matter_allow_logins' , 'no' ) ); ?> />
	<p class="description"><?php _e( 'Dark Matter logs out users on the mapped domains if they are not logged in on the admin domain.', 'dark-matter' ); ?></p>
	<p class="description"><?php _e( 'Check this box to disable that behaviour. Useful for websites with membership like functionality.', 'dark-matter' ); ?></p>
<?php }

function dark_matter_blog_admin_menu() {
	if ( false === is_main_site() ) {
		add_options_page( __( 'Domain Mapping', 'dark-matter' ), __( 'Domain Mapping', 'dark-matter' ), 'activate_plugins', 'dark_matter_blog_settings', 'dark_matter_blog_domain_mapping' );
	}
}
add_action( 'admin_menu', 'dark_matter_blog_admin_menu' );

function dark_matter_blog_domain_mapping() {
	$domains = dark_matter_api_get_domains();

	if ( false === current_user_can( 'activate_plugins' ) ) {
		wp_die( __( 'Insufficient permissions.', 'dark-matter' ) );
	}
?>
	<div class="wrap dark-matter-blog">
		<h1><?php _e( 'Domain Mapping for this Blog', 'dark-matter' ); ?></h1>
		<form method="post" action="options.php">
			<?php
				settings_fields( 'dark_matter_blog' );
				do_settings_sections( 'dark_matter_blog' );

				submit_button();
			?>
		</form>
		<h2><?php _e( 'Mapped Domains', 'dark-matter' ); ?></h2>
		<table id="dark-matter-blog-domains" class="domains" data-delete-nonce="<?php echo( wp_create_nonce( 'delete_nonce' ) ); ?>" data-primary-nonce="<?php echo( wp_create_nonce( 'primary_nonce' ) ); ?>">
			<thead>
				<tr>
					<th>#</th>
					<th><?php esc_html_e( 'Domain', 'dark-matter' ); ?></th>
					<th><?php esc_html_e( 'Features', 'dark-matter' ); ?></th>
					<th><?php esc_html_e( 'Action', 'dark-matter' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach ( $domains as $domain ) {
						dark_matter_blog_domain_mapping_row( $domain );
					}
				?>
			</tbody>
		</table>
		<h2><?php _e( 'Add New Domain', 'dark-matter' ); ?></h2>
		<form action="<?php echo( admin_url( 'admin.php?action=dm_add_domain' ) ); ?>" method="POST">
			<input id="dm_new_nonce" name="dm_new_nonce" type="hidden" value="<?php echo( wp_create_nonce( 'darkmatter-add-domain' ) ); ?>" />
			<p>
				<label>
					<?php esc_html_e( 'Domain', 'dark-matter' ); ?> :
					<input id="dm_new_domain" name="dm_new_domain" type="text" value="" />
				</label>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Is Primary?', 'dark-matter' ); ?> :
					<input id="dm_new_is_primary" name="dm_new_is_primary" type="checkbox" value="yes" />
				</label>
			</p>
			<p>
				<label>
					<?php esc_html_e( 'Is HTTPS?', 'dark-matter' ); ?> :
					<input id="dm_new_is_https" name="dm_new_is_https" type="checkbox" value="yes" />
				</label>
			</p>
			<p>
				<button id="dm_new_add_domain" class="button button-primary"><?php esc_html_e( 'Add Domain', 'dark-matter' ); ?></button>
			</p>
		</form>
	</div>
<?php }

function dark_matter_blog_domain_mapping_row( $data ) {
	$features = array();
	$base_actions_url = admin_url( 'admin.php?id=' . $data->id );

	if ( $data->is_primary ) {
		$features[] = 'Primary';
	}

	if ( $data->is_https ) {
		$features[] = 'HTTPS';
	}

	if ( $data->active ) {
		$features[] = 'Active';
	}
?>
	<tr id="domain-<?php echo( $data->id ); ?>" data-id="<?php echo( $data->id ); ?>" data-primary="<?php echo( $data->is_primary ); ?>">
		<th scope="row"><?php echo( $data->id ); ?></th>
		<td>
			<?php printf( '<a href="%2$s://%1$s">%1$s</a>', $data->domain, ( $data->is_https ? 'https' : 'http' ) ); ?>
		</td>
		<td>
			<?php echo( implode( ', ', $features ) ); ?>
		</td>
		<td>
			<?php if ( empty( $data->is_primary ) ) : ?>
				<a class="primary-domain button"  href="<?php echo( wp_nonce_url( add_query_arg( 'action', 'dm_new_primary_domain', $base_actions_url ), 'darkmatter-new-primary-domain', 'dm_new_primary_nonce' ) ); ?>" title="<?php printf( esc_attr( __( 'Make %s the primary domain for this blog.', 'dark-matter' ) ), $data->domain ); ?>"><?php esc_html_e( 'Make Primary', 'dark-matter' ); ?></a>
			<?php endif; ?>

			<a class="primary-domain button"  href="<?php echo( wp_nonce_url( add_query_arg( 'action', ( $data->is_https ? 'dm_unset_domain_https' : 'dm_set_domain_https' ), $base_actions_url ), 'darkmatter-set-https-domain', 'dm_set_https_nonce' ) ); ?>" title="<?php printf( esc_attr( __( 'Add HTTPS to %s this domain.', 'dark-matter' ) ), $data->domain ); ?>">
				<?php echo( $data->is_https ? __( 'Remove HTTPS', 'dark-matter' ) : __( 'Add HTTPS', 'dark-matter' ) ); ?>
			</a>

			<a class="button" href="<?php echo( wp_nonce_url( add_query_arg( 'action', 'dm_del_domain', $base_actions_url ), 'darkmatter-delete-domain', 'dm_del_nonce' ) ); ?>"><?php esc_html_e( 'Delete', 'dark-matter' ); ?></a>
		</td>
	</tr>
<?php }
