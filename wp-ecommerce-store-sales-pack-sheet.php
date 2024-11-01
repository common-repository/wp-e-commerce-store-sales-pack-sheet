<?php
/**
* @ * @package WP e-Commerce Store Sales Pack Sheet
*/
/*
Plugin Name: WP e-Commerce Store Sales Pack Sheet
Plugin URI: http://www.earthbound.com/plugins/packsheetplugin
Description: Store Sales Pack Sheet feature for WP e-Commerce, generates sales 'pack sheet' report, showing item sale totals, grouped into multiple zip code 'areas'. Especially useful for delivery based businesses using WP e-Commerce.
Author: Adam Silverstein
Version: 0.6.1
Author URI: http://www.earthbound.com/
License: GPLv2 or later
*/
/*
	TODO:
		-add overall pack sheet report
		-add generated tag at top
 		-export/import area list
*/

class WPEcmmerceAdvancedReporting {  
    public function __construct()   {
		if ( is_admin() ) {
			add_action( 'admin_menu' ,  array( $this, 'packsheet_menu' ) );
			add_action( 'admin_init' ,  array( $this, 'packsheet_menu_init' ) );
		}
	}
	function packsheet_menu() {
			add_dashboard_page( 'Sales Pack Sheet', 'Sales Pack Sheet', 'manage_options', 'packsheet_menu', array( $this, 'packsheet_options' ) );
	}
	
	function packsheet_menu_init() {
			add_action( 'admin_footer', array( $this, 'packsheet_admin_footer' ) );
			add_action( 'admin_print_scripts', array( $this, 'packsheet_plugin_admin_scripts' ) );
			add_filter( 'plugin_action_links', array( $this, 'packsheet_action_links'), 10, 2 );
			load_plugin_textdomain( 'packsheet', false, dirname( plugin_basename( __FILE__ ) ) ); 

	}
	
	function packsheet_action_links( $links, $file ) {
 		if ( $file == plugin_basename( __FILE__ ) ) {
			$post_links =  sprintf( '<a href="index.php?page=packsheet_menu">%s</a>',  esc_html__( 'Settings', 'packsheet' ) );
			
			// make the 'Settings' link appear first
			array_unshift( $links, $post_links );
		}
 		return $links;
	}
	
	function packsheet_plugin_admin_scripts() {
		$pluginfolder = get_bloginfo( 'url' ) . '/' . PLUGINDIR . '/' . dirname( plugin_basename( __FILE__ ) );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-printelement', $pluginfolder . '/js/jquery.printElement.min.js', array( 'jquery', 'jquery-ui-core') );
		//jquery.printElement.min.js
		wp_enqueue_style( 'jquery.ui.theme', $pluginfolder . '/css/ui-lightness/jquery-ui-1.9.2.custom.min.css' );    
		?>
<?php
	}
		
	function packsheet_admin_footer() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('.weardatepicker').datepicker({
				dateFormat : 'yy-mm-dd'
			});
			
			jQuery( 'input#printreport' ).on( 'click', function() {
 				jQuery('div#reportareas').printElement({printMode:'popup'});
			});
			
			jQuery( '.removalbox' ).slideUp( 'fast' ).hide();
                //TO-DO: makes these submenu openings persist, save state in cookies?
                jQuery( 'a.packsheetremoval' ).click( function() {
                    inner=jQuery( this ).next( '.removalbox' );
                    if ( jQuery( inner ).is( ":hidden" ) ) {
                        jQuery( inner ).show().slideDown( 'fast' );
                        jQuery( this ).removeClass( 'unselected' ).addClass( 'selected' );
						jQuery( this ).text( '<?php esc_html_e( 'cancel remove', 'packsheet' )?>' );
                    } else {
                        jQuery( inner ).slideUp( 'fast' ).hide();
                        jQuery( this ).removeClass( 'selected' ).addClass( 'unselected' );
						jQuery( this ).text( '<?php esc_html_e( 'remove area', 'packsheet' )?>' );

                    }
            } );
		});
	</script>
		<?php
	}
		function packsheet_options() {
			global $wpdb;
 			
			$table_name = $wpdb->prefix . 'packsheet_deliveryzones';
			$default_to_date = date( 'Y-m-d' );
			$default_from_date = date( 'Y-m-d', strtotime( "-1 week" ) );
			$removeword = __( 'Remove', 'packsheet') ;
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
			
			//ungroupedreport
			$ungroupedreport=false;
			if( isset( $_POST[ 'ungroupedreport' ] ) ) {
				$ungroupedreport=true;
			}
			//clear area?
			if( isset( $_POST[ 'removearea' ] ) ) {
			//Take action?
				if ( ! wp_verify_nonce($nonce, 'packsheet-admin' ) ) {
						 die('Security check'); 
					}	$areatoremove = substr( $_POST[ 'removearea' ], strlen( $removeword )+1 );
			 	//echo 'remove '.$areatoremove;
				$wpdb->query( 
					$wpdb->prepare( "
						DELETE FROM $table_name
						WHERE deliveryzone_zonename = '%s' ",
						$areatoremove
					)
				);
			}
			
			//add area?
			if ( isset( $_POST[ 'createziparea' ] ) ) {
				//Take action?
				if ( ! wp_verify_nonce($nonce, 'packsheet-admin' ) ) {
						 die('Security check'); 
					}
				if  ( isset( $_POST[ 'areaname' ] ) && isset( $_POST[ 'zipcodelist' ] ) ) {
					//check if this is a list or single zip
					$zipname = explode( ',', $_POST[ 'zipcodelist' ] );
					foreach ($zipname as $azipname ) {
						$wpdb->insert( 
							$table_name, 
							array( 
							'devileryzone_zipcode' => $azipname , 
							'deliveryzone_zonename' => $_POST[ 'areaname' ] 
							), 
							array( 
							'%s', 
							'%s' 
							) 
						);
					}
				}
			
			}
	//display the admin page		
	?>
     <div class="wrap">
        <h2><?php _e( 'WP e-Commerce Store Sales Pack Sheet', 'packsheet' ); ?></h2>
        <form id="form1" name="form1" method="post" action="">
		<?php
			if ( function_exists('wp_nonce_field') ) {
				wp_nonce_field('packsheet-admin'); 
			}
        ?>
          <h3><?php _e( 'Report Date Range:', 'packsheet' ); ?></h3>
                <input type="text" name="fromdate" id="fromdate" class="weardatepicker" 
                	value="<?php echo ( isset ( $_POST[ 'fromdate' ] ) ? $_POST[ 'fromdate' ] : $default_from_date ); ?>" /> 
                <label for="to"></label>
                <?php _e( 'to', 'packsheet'); ?>
                <input type="text" name="todate" id="todate" class="weardatepicker"  
                	value="<?php echo ( isset ( $_POST[ 'todate' ] ) ? $_POST[ 'todate' ] : $default_to_date ); ?>"/>
              <br />
              <input name="viewreport" type="submit" value="<?php _e( 'Totals Report - Grouped by area', 'packsheet'); ?>" id="viewreport" />
              <br />
              <input name="ungroupedreport" type="submit" value="<?php _e( 'Totals Report - Not grouped by area', 'packsheet'); ?>" id="ungroupedreport" />
              <br />
            <input name="printreport" type="button" value="<?php _e( 'Print Report', 'packsheet'); ?>" id="printreport" />
            
  <div id="reportareas">
  	<style type="text/css">
		.reportrow {
			padding-top: 5px;
			padding-right: 10px;
			padding-bottom: 5px;
			padding-left: 10px;
		}
		.even {
			background-color:#EEEEEE;
		}
		.odd {
			background-color: #FFFFFF;
		}
		div#reportareas ,div#reportareaoptions{
			border: 1px solid #000;
			margin-bottom:15px;

			padding:10px;
 
		}
		.reportheader{
			background-color:#000000;
			color:#FFFFFF;
			font-size:18px;
			padding:10px;
			margin-top:15px;

		}
    </style>

			<?php
				$areaquery="
					SELECT DISTINCT devileryzone_zipcode, deliveryzone_zonename  
					FROM  $table_name ORDER BY deliveryzone_zonename ";
				if ( $arealist = $wpdb->get_results ( $areaquery ) ) {
                ?>     
		<?php
		//run the report
        $totalQuery = 
			"
			SELECT *, devileryzone_zipcode, deliveryzone_zonename, user_ID, SUM(cc.quantity) AS total, cc.name AS productname 
			FROM  wp_wpsc_submited_form_data sf, $table_name, wp_wpsc_cart_contents cc 
			INNER JOIN wp_wpsc_purchase_logs pl ON cc.purchaseid = pl.id 
			WHERE FROM_UNIXTIME(date) BETWEEN %s AND %s AND form_id='17' AND log_id=pl.id AND  pl.processed >= 0  
			AND sf.value=devileryzone_zipcode AND pl.processed != 1 AND pl.processed !=6";
			if( !$ungroupedreport ) {	
				$totalQuery.=" GROUP BY deliveryzone_zonename, cc.name  ";
			} else {
				$totalQuery.=" GROUP BY cc.name  ";
			}
		/*echo ( sprintf ($totalQuery,isset( $_POST[ 'fromdate' ] ) ? $_POST[ 'fromdate' ] . ' 00:00:00' : $default_from_date, 
			isset( $_POST['todate'] ) ? $_POST[ 'todate' ] . ' 23:59:59' : $default_to_date ) );*/
        $totlist = $wpdb->get_results( 	
						$wpdb->prepare( $totalQuery, 
						isset( $_POST[ 'fromdate' ] ) ? $_POST[ 'fromdate' ] . ' 00:00:00'  : $default_from_date . ' 00:00:00' , 
						isset( $_POST['todate'] ) ? $_POST[ 'todate' ] . ' 23:59:59' : $default_to_date . ' 23:59:59'
						) 
					);
        $zonename="";
		echo( '<h2>' );
		echo( sprintf( __( 'Report generated on %s. '), date( 'F jS,  Y h:i:s A' ) ) );
		echo ( sprintf( __( 'Covers dates: %s to %s'),			
			isset( $_POST[ 'fromdate' ] ) ? $_POST[ 'fromdate' ]  : $default_from_date  , 
			isset( $_POST[ 'todate' ] ) ? $_POST[ 'todate' ]  : $default_to_date 
		 ) ) ;
		echo ('</h2>' );

		
        foreach ( $totlist as $atotal ) {

			if (  ($zonename != $atotal->deliveryzone_zonename) && ( !$ungroupedreport ) ) { 
				$evenodd=0;
				echo( '<div class="reportrow reportheader">' );
				//display the zone name once at the top of each section
				$zonename = $atotal->deliveryzone_zonename;
				echo( $zonename );
				echo( '</div>' );
			}
			echo( sprintf ('<div class="reportrow %s">', ( 0 == ($evenodd++ % 2 ) ) ? 'even' : 'odd' ) );
 			echo( $atotal->productname );
			echo( ' - QTY: ' );
			echo( $atotal->total  );
			echo( '<br />' );
			echo( '</div>' );
		}
		//show report area options
		?>
        </div>
        <div id="reportareaoptions">
		<h3><?php _e( 'Report Areas:', 'packsheet' ); ?></h3>
                  <?php
				 $areaname='';
				 $areacount=0;
				 foreach ( $arealist as $anarea ) {
				 	if ( $anarea->deliveryzone_zonename != $areaname ) {
						if ( $areacount++ > 0 ) {
							echo( ' - <a href="javascript:;" class="packsheetremoval">remove area</a>' );
							echo( '<div class="removalbox">' );
							echo( '<input name="removearea"  type="submit" value="' . $removeword . ' ' . $areaname . '" id="removearea" />' );
							echo( '</div>' );
							echo( '<br />' );
						}
 						echo( '<div class="packsheet-zonename"><strong>' . $anarea->deliveryzone_zonename . '</strong></div>' );
						echo( $anarea->devileryzone_zipcode );
						$areaname=$anarea->deliveryzone_zonename;
					} else {
						echo( ', '. $anarea->devileryzone_zipcode );
					}
				 }
				//echo (' - <a href="?remove='.$areaname.'" class="packsheetremoval">remove</a><br />');
							echo (' - <a href="javascript:;" class="packsheetremoval">remove area</a><div class="removalbox">
										  <input name="removearea"  type="submit" value="'.__( 'Remove Area', 'packsheet') .'" id="removearea"  />
									  </div> <br />');
	
	} //end IF deliveryzones
	else {
		$areaquery= 
			"CREATE TABLE IF NOT EXISTS $table_name (
			deliveryzoneid int(11) NOT NULL AUTO_INCREMENT,
			devileryzone_zipcode varchar(255) DEFAULT NULL,
			deliveryzone_zonename varchar(255) DEFAULT NULL,
			PRIMARY KEY (deliveryzoneid)
			);";
		echo _e( 'Enter at least one area to begin.', 'packsheet' );
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $areaquery );
	}
?>  
     <br />
                 <strong>
                <?php _e( 'Add Area', 'packsheet'); ?>
                </strong>                       <br />
                <?php _e( 'Comma separate zip codes when entering more than one', 'packsheet'); ?>
                <br />
                <label for="areaname">
                <?php _e( 'Area name', 'packsheet'); ?>
                </label>
                <input type="text" name="areaname" id="areaname" />
                <br />
                <label for="zipcodelist">
                <?php _e( 'Zip(s)', 'packsheet'); ?>
                <input name="zipcodelist" type="text" /> 
                </label>
                <br />
                <input name="createziparea" type="submit" value="<?php _e( 'Create/Add to Zip Code Area', 'packsheet'); ?>" id="createziparea" />
          </div>
        </form>
    </div>

<?php
	}
}  
 
$WPEcmmerceAdvancedReporting = new WPEcmmerceAdvancedReporting();  
