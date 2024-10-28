<?php
/*
 * Plugin Name:       5 ways... Ustawienia widgetu czatu, obsługi spraw, generatora ankiet
 * Plugin URI:        https://5ways.com
 * Description:       Plugin umożliwiający konfigurację skryptu czatu, obsługi spraw, ankiet 5 Ways..
 * Version:           1.1.1
 * Tested up to: 6.4
 * Requires at least: 6.4
 * Requires PHP:      7.2
 * Author:            5 ways...
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Dodajemy menu ustawień dla administratora
function five_ways_menu()
{
	add_menu_page(
		'5 ways... Ustawienia widgetu czatu, obsługi spraw, ankiet',
		'5 ways... Ustawienia widgetu czatu, obsługi spraw, ankiet',
		'manage_options',
		'five_ways_settings',
		'five_ways_settings_page'
	);
}

add_action('admin_menu', 'five_ways_menu');

// Dodajemy stronę ustawień
function five_ways_settings_page()
{
	?>
    <div class="wrap">
        <h1>5 ways... Ustawienia widgetu czatu, obsługi spraw, ankiet</h1>
        <form method="post" action="options.php">
			<?php settings_fields('five_ways_settings_group'); ?>
			<?php do_settings_sections('five_ways_settings'); ?>
			<?php settings_errors('five_ways_settings_group'); ?>
            <div id="widget_ids_container">
				<?php
				// Wczytujemy istniejące wartości widgetId
				$options = get_option('five_ways_settings');
				if (!empty($options['widget_ids'])) {
					foreach ($options['widget_ids'] as $widget_id) {
						echo '<div class="remove" style="margin: 10px 0"><input type="text" name="five_ways_settings[widget_ids][]" value="' . esc_attr($widget_id) . '" data-widget-id="' . esc_attr($widget_id) . '"/><a href="#" class="remove_widget_id" style="margin-left: 20px">Usuń widget</a></div>';

					}
				}
				?>
            </div>
            <button type="button" id="add_widget_id" style="">Dodaj widget
            </button>
            <br/><br/>
			<?php submit_button('Zapisz zmiany', 'primary', 'submit', true, null); ?>
        </form>
    </div>
	<?php
}

function five_ways_admin_scripts()
{
	?>
    <script>
        jQuery(document).ready(function ($) {
            $('#add_widget_id').click(function () {
                $('#widget_ids_container').append('<div style="margin: 10px 0"><input type="text" name="five_ways_settings[widget_ids][]" value=""/><a href="#" class="remove_widget_id" style="margin-left: 20px">Usuń widget</a></div>');
            });

            $(document).on('click', '.remove_widget_id', function () {
                var widget_id = $(this).siblings('input[type="text"]').data('widget-id');
                $(this).parent().remove(); // Usuwamy pole z widoku
                $(this).closest('input').remove();
                $('script[widgetId="' + widget_id + '"]').remove(); // Usuwamy skrypt z footer'a
            });
        });
    </script>
	<?php
}

add_action('admin_footer', 'five_ways_admin_scripts');


// Dodajemy pola do formularza ustawień
function five_ways_settings_init()
{
	register_setting(
		'five_ways_settings_group',
		'five_ways_settings',
		'five_ways_settings_validate'
	);

	add_settings_section(
		'five_ways_settings_section',
		'Ustawienia Skryptu',
		'five_ways_settings_section_callback',
		'five_ways_settings'
	);

	add_settings_field(
		'five_ways_selected_pages',
		'Wybierz stronę',
		'five_ways_selected_pages_callback',
		'five_ways_settings',
		'five_ways_settings_section'
	);

	add_settings_field(
		'five_ways_widget_ids',
		'Wartości widgetId',
		'five_ways_widget_ids_callback',
		'five_ways_settings',
		'five_ways_settings_section'
	);
}

add_action('admin_init', 'five_ways_settings_init');

// Wyświetlamy pole wprowadzania wartości widgetId

function five_ways_widget_ids_callback()
{
	$options = get_option('five_ways_settings');
	$widget_ids = isset($options['widget_ids']) ? $options['widget_ids'] : array();

}

// Dodajemy sekcję do formularza ustawień
function five_ways_settings_section_callback()
{

}

// Wybór konkretnej strony
function five_ways_selected_pages_callback()
{
	$options = get_option('five_ways_settings');
	$selected_pages = isset($options['selected_pages']) ? $options['selected_pages'] : array();
	$pages = get_pages();
	$home_page_id = get_option('page_on_front');

	if (!wp_script_is('select2', 'enqueued')) {
		wp_enqueue_script('select2', plugin_dir_url(__FILE__) . 'select2/select2.min.js', array('jquery'), '4.1.0-rc.0', true);
	}

	echo '<script>
        jQuery(document).ready(function() {
            var selectedPages = ' . esc_js(wp_json_encode($selected_pages)) . ';
            var pagesList = ' . esc_js(wp_json_encode($pages)) . ';
 
            jQuery("#five_ways_selected_pages").select2({
               inimumResultsForSearch: -1, // Trigger search after 1 characters
                placeholder: "Wyszukaj stronę",
                allowClear: false, // Enable clearing all selections,
             
                templateResult: function(data) {
                    if (data.id === "") { // placeholder for search input
                        return data.text;
                    }
 
                    const selected = selectedPages.includes(data.id) ? "selected" : "";
                    return jQuery("<option " + selected + "></option>").text(data.text);
                }
            });
        });
    </script>';

	echo '<div class="five-ways-page-select">';
	echo '<select id="five_ways_selected_pages" class="js-example-basic-single" name="five_ways_settings[selected_pages][]"  data-live-search="true">';

	// Dodajemy opcję dla strony głównej do listy rozwijanej
	echo '<option value="' . esc_attr($home_page_id) . '" ' . (in_array($home_page_id, $selected_pages) ? 'selected' : '') . '>Strona główna</option>';

	foreach ($pages as $page) {
		$checked = in_array($page->ID, $selected_pages) ? 'selected' : '';
		echo '<option value="' . esc_attr($page->ID) . '" ' . checked( true, $checked, false ) . '>' . esc_html($page->post_title) . '</option>';
	}

	echo '</select>';
	echo '</div>';

	foreach ($pages as $page) {
		if (in_array($page->ID, $selected_pages)) {
			$checked = 'checked';
			echo '<p><label><input type="checkbox" name="five_ways_settings[selected_pages][]" value="' . esc_attr($page->ID) . '" ' . esc_attr($checked) . '> ' . esc_html($page->post_title) . '</label></p>';
		}
	}
	// Dodajemy opcję dla strony głównej, jeśli jest zaznaczona

	if (in_array($home_page_id, $selected_pages)) {
		$home_page_checked = 'checked';
		echo '<p><input type="checkbox" name="five_ways_settings[selected_pages][]" value="' . esc_attr($home_page_id) . '" ' . esc_attr($home_page_checked) . '> Strona główna</p>';
	}

}


// Funkcja do walidacji wprowadzonych danych
function five_ways_settings_validate($input)
{
	$output = array();

	if (isset($input['widget_ids'])) {
		$output['widget_ids'] = array_map('sanitize_text_field', $input['widget_ids']);
	}

	if (isset($input['selected_pages'])) {
		$output['selected_pages'] = $input['selected_pages'];
	}

	if (isset($input['exclude_scripts'])) {
		$output['exclude_scripts'] = $input['exclude_scripts'];
	}

	return $output;
}


// Wstrzykujemy skrypt do stopki strony
function five_ways_enqueue_script()
{
	$options = get_option('five_ways_settings');
	$widget_ids = isset($options['widget_ids']) ? $options['widget_ids'] : array();
	$selected_pages = isset($options['selected_pages']) ? $options['selected_pages'] : array();
	$current_page_id = get_queried_object_id();

	// Tworzymy tablicę na unikalne widgetId
	$unique_widget_ids = array();

	// Dodajemy unikalne widgetId do tablicy
	foreach ($widget_ids as $widget_id) {
		if (!empty($widget_id) && !in_array($widget_id, $unique_widget_ids)) {
			$unique_widget_ids[] = $widget_id;
		}
	}

	// Dodajemy skrypty na podstawie dodanych wartości widgetId
	foreach ($unique_widget_ids as $widget_id) {
		// Sprawdzamy czy bieżąca strona znajduje się na liście wybranych stron
		if (!empty($widget_id) && !empty($selected_pages) && in_array($current_page_id, $selected_pages)) {
			// Dodajemy skrypt jako inline script
			echo '<script defer src="https://chat.5ways.com/assets/scripts/widget.js" widgetId="' . esc_js($widget_id) . '";></script>';
		}
	}
}

add_action('wp_footer', 'five_ways_enqueue_script');

// Dodawanie stylów CSS
function five_ways_enqueue_styles()
{
	// Podlinkowanie do stylu Select2
	wp_enqueue_style('select2', plugin_dir_url(__FILE__) . 'select2/select2.min.css', array(), '4.1.0-rc.0');

	// Podlinkowanie do Twojego pliku CSS w katalogu twojej wtyczki
	wp_enqueue_style('five-ways-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
}

add_action('admin_enqueue_scripts', 'five_ways_enqueue_styles');

// Funkcja obsługująca shortcode
function five_ways_script_shortcode($atts)
{
	// Domyślne atrybuty shortcode'u
	$atts = shortcode_atts(
		array(
			'widget_id' => '', // Usunięcie domyślnego widget_id
		),
		$atts,
		'5ways_script' // Nazwa shortcode'u
	);

	// Pobieramy widgetId z atrybutów shortcode'u
	$widget_id = $atts['widget_id'];

	// Sprawdzamy, czy podano widgetId, jeśli nie, zwracamy komunikat błędu
	if (empty($widget_id)) {
		return '<p style="color: red;">Błąd: Brak wartości dla widgetId. Podaj wartość atrybutu widget_id.</p>';
	}

	// Zwracamy skrypt jako string
	$script = '<script defer src="https://chat.5ways.com/assets/scripts/widget.js" widgetId="' . esc_attr($widget_id) . '"></script>';

	return $script;
}

// Rejestrujemy shortcode
add_shortcode('5ways_script', 'five_ways_script_shortcode');

?>

