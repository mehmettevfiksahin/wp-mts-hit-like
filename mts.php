<?php
/*
Plugin Name: MTS Hit Like 
Plugin URI: http://mehmettevfiksahin.com.tr
Version: 1.0
Author: Mehmet Tevfik ŞAHİN
Destription: Makaleler için kullanışlı beğen butonu ve beğeniye göre sıralanmış widget içerir aynı zamanda tüm etiketleri listeleyebileceğiniz bir sistemide mevcuttur.
*/

if(!defined ('ABSPATH')) exit;
function buton_ekle($content){
	if (is_single()) {
		global $wpdb;
		$table_name=$wpdb->prefix. "likes";
		is_user_logged_in() ? $user_id = wp_get_current_user()->ID : $user_id='0';
		$post_id=get_the_ID();
		$check = $wpdb->get_row("SELECT * FROM $table_name where user_id = $user_id AND post_id = $post_id");
		if($check){
			$active=" active";
			$button="fa-heart";
		}else{
			$active="";
			$button="fa-heart-o";
		}
		$content .= '<span class="like'.$active.'" onclick="begen('.$post_id.','.$user_id.')"><i class="fa '.$button.'"></i></span>';
	}
	return $content;
}
function ajax_ayar(){
	wp_enqueue_style('font-awesome', plugins_url('/dok/css/font-awesome.min.css',__FILE__));

	wp_enqueue_style('mts-stil', plugins_url('/dok/css/stil.css',__FILE__));
	
	wp_enqueue_script('jquery320', '//code.jquery.com/jquery-3.2.0.min.js');
	
	wp_enqueue_script('my-ajax', plugins_url('/dok/js/ajax.js',__FILE__),array('jquery'), true);
	wp_localize_script('my-ajax', 'my_ajax_url', array(
		'ajax_url' => admin_url('admin-ajax.php')
	));
}
function begen_birak(){
	global $wpdb;
	$table_name = $wpdb->prefix . "likes";
	$user_id = $_POST['user_id'];
	$post_id = $_POST['post_id'];
	$y=array();
	if(defined('DOING_AJAX') && DOING_AJAX){
		if($user_id==0 || $user_id==""){
			$y['hata'] = 'Giriş Yapman gerek..';
		}else{
			$check = $wpdb->get_row("SELECT * FROM $table_name where user_id = $user_id AND post_id = $post_id");
			if($check){
				$deled = $wpdb->delete( $table_name, array ('user_id' => $user_id, 'post_id' => $post_id ), array( '%d', '%d' )  );
				if($deled){
					$y['ok'] = 'Silindi.';
					$y['tok'] = 0;
				}else{
					$y['hata'] = 'Olmadı !';
				}
			}else{
				$sql=$wpdb->insert(
					''.$table_name.'',
					array(
						'user_id' => $user_id,
						'post_id' => $post_id,
						'like_life' => '1',
					)
				);
				if($sql){
					$y['ok'] = 'Başarıyla Beğendin!';
					$y['tok'] = 1;
					
				}else{
					$y['hata'] = 'Bilinmeyen bir hata meydana geldi daha sonra tekrar dene.';
				}
			}
		}
	}else{
		$y['hata'] = "Beklenmedik hata..";
	}
	echo json_encode($y);
	die();
}
function vt_olustur(){
	global $wpdb;
	$table_name = $wpdb->prefix . "likes";
	$charset_collate = $wpdb->get_charset_collate();
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		user_id varchar(255) NOT NULL,
		post_id varchar(255) NOT NULL,
		like_life varchar(255) DEFAULT '0' NOT NULL,
		time timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		UNIQUE KEY id (id)
	) $charset_collate;";	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
function dwwp_job_taxonmy_list($atts, $content = null){
    $page = ( get_query_var('paged') ) ? get_query_var( 'paged' ) : 1;
    // number of tags to show per-page
    $per_page = 10;
    $offset = ( $page-1 ) * $per_page;
    $args = array( 'number' => $per_page, 'offset' => $offset, 'hide_empty' => 0,'orderby' => 'count','order' => 'DESC' );
	$taxonomy = 'post_tag';
	$tax_terms = get_terms( $taxonomy, $args );
	echo '<div id="mts-tags">';
	foreach ($tax_terms as $tax_term) {
		echo '<div class="mts-tag"><a href="' . esc_attr(get_term_link($tax_term, $taxonomy)) . '">' . $tax_term->name.'<span>'.$tax_term->count.'</span></a></div>';
	}
	echo '<div class="temizle"></div></div>';
    $total_terms = wp_count_terms( 'post_tag' );
    $pages = ceil($total_terms/$per_page);
    if( $pages > 1 ):
        echo '<div class="mts-page">';
        for ($pagecount=1; $pagecount <= $pages; $pagecount++):
            echo '<a href="'.get_permalink().'page/'.$pagecount.'/">'.$pagecount.'</a>';
        endfor;
        echo '</div>';
    endif;
}
class mts_popular_post extends WP_Widget {
	function __construct(){
		parent::__construct(false, $name = __('MTS Popüler Yazılar'));
	}
	function widget($args,$instance){
		?>
		<section id="mts-popular-posts" class="widget">
			<h2 class="widget-title">Popüler Yazılar</h2>
			<ul>
				<?php
					global $wpdb;
					$table_like = $wpdb->prefix . "likes";
					$table_post = $wpdb->prefix . "posts";
					$query = $wpdb->get_results("SELECT COUNT(l.post_id) as say, l.post_id FROM $table_like as l GROUP BY l.post_id order by say desc");
					$idlist=array();
					if($query){
						foreach($query as $x){
							$idlist[] = $id = $x->post_id;
							$the_query = new WP_Query( array("p"=> $id) );
							if ( $the_query->have_posts() ){
								$the_query->the_post();
								echo '<li><a href="'; the_permalink(); echo '">'; the_title(); echo '<span>'.$x->say.'</span></a></li>';
							}
						}
					}
			?>
			</ul>
		</section>
		<?php
	}
}


add_action('the_content', 'buton_ekle');
add_action('wp_enqueue_scripts', 'ajax_ayar');
add_action('wp_ajax_begen_birak', 'begen_birak');
add_action('wp_ajax_nopriv_begen_birak', 'begen_birak');
register_activation_hook(__FILE__, 'vt_olustur');
add_action('widgets_init', function(){register_widget('mts_popular_post');});
add_shortcode('etiket_listele', 'dwwp_job_taxonmy_list');
?>