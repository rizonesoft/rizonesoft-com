<?php

function gdm_logs_export_tab_page() {
	//    jQuery functions
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_style( 'gdm_jquery_ui_style' );

	// datetime fileds
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Read-only date range selection, nonce verified on export
	if ( isset( $_POST['gdm_stats_start_date'] ) ) {
		$start_date = sanitize_text_field( wp_unslash( $_POST['gdm_stats_start_date'] ) );
	} else {
		// default start date is 30 days back
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Intentionally using date() for timezone-aware display
		$start_date = date( 'Y-m-d', time() - 60 * 60 * 24 * 30 );
	}

	if ( isset( $_POST['gdm_stats_end_date'] ) ) {
		$end_date = sanitize_text_field( wp_unslash( $_POST['gdm_stats_end_date'] ) );
	} else {
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date -- Intentionally using date() for timezone-aware display
		$end_date = date( 'Y-m-d', time() );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	?>

	<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
		<p><?php esc_html_e( 'This menu allows you to export all the log entries to a CSV file that you can download. The download link will be shown at the top of this page.', 'gluon-download-manager' ); ?></p>
	</div>

	<div id="poststuff">
		<div id="post-body">
			<div class="postbox">
				<h3 class="hndle"><label
							for="title"><?php esc_html_e( 'Choose Date Range (yyyy-mm-dd)', 'gluon-download-manager' ); ?></label>
				</h3>
				<div class="inside">
					<form id="gdm_choose_logs_date" method="post"
						onSubmit="return confirm('Are you sure you want to export all the log entries?');">
						<div>
							<label for="gdm_stats_start_date_input"><?php esc_html_e( 'Start Date: ', 'gluon-download-manager' ); ?></label>
							<input type="text"
								   id="gdm_stats_start_date_input"
								   class="datepicker d-block w-100"
								   name="gdm_stats_start_date"
								   value="<?php echo esc_attr( $start_date ); ?>">
							<label for="gdm_stats_end_date_input"><?php esc_html_e( 'End Date: ', 'gluon-download-manager' ); ?></label>
							<input type="text"
								   id="gdm_stats_end_date_input"
								   class="datepicker d-block w-100"
								   name="gdm_stats_end_date"
								   value="<?php echo esc_attr( $end_date ); ?>">
						</div>
						<br>
						<div id="gdm_logs_date_buttons">
							<?php // phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date -- date() is intentionally used for timezone-aware display ?>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'Today', 'gluon-download-manager' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-m-01' ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Month', 'gluon-download-manager' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'first day of last month' ) ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d', strtotime( 'last day of last month' ) ) ); ?>"><?php esc_html_e( 'Last Month', 'gluon-download-manager' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-01-01' ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'This Year', 'gluon-download-manager' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo esc_attr( date( 'Y-01-01', strtotime( '-1 year' ) ) ); ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-12-31', strtotime( 'last year' ) ) ); ?>"><?php esc_html_e( 'Last Year', 'gluon-download-manager' ); ?></button>
							<button class="button" type="button"
									data-start-date="<?php echo '1970-01-01'; ?>"
									data-end-date="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"><?php esc_html_e( 'All Time', 'gluon-download-manager' ); ?></button>
							<?php // phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date ?>
						</div>

						<div class="submit">
							<input type="submit" class="button-primary" name="gdm_export_log_entries"
								   value="<?php esc_html_e( 'Export Log Entries to CSV File', 'gluon-download-manager' ); ?>"/>
						</div>
						<?php wp_nonce_field( 'gdm_export_logs', 'gdm_export_logs_nonce' ); ?>
					</form>
				</div>
			</div>

		</div>
	</div>

	<?php
}

?>

<script>
	jQuery(document).ready(function () {
		jQuery('#gdm_logs_date_buttons button').click(function (e) {
			jQuery('#gdm_choose_logs_date').find('input[name="gdm_stats_start_date"]').val(jQuery(this).attr('data-start-date'));
			jQuery('#gdm_choose_logs_date').find('input[name="gdm_stats_end_date"]').val(jQuery(this).attr('data-end-date'));
		});

		jQuery('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd'
		});
	});
</script>
