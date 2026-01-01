<?php

namespace AsmaaSalon\API\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Booking Settings (UI-only settings persistence)
 *
 * Frontend components copied from customer-dashboard expect:
 * - GET  /wp-json/asmaa-salon/v1/booking/settings/all
 * - GET  /wp-json/asmaa-salon/v1/booking/settings/{section}
 * - POST /wp-json/asmaa-salon/v1/booking/settings/{section}
 *
 * We persist settings in wp_options using option names:
 * - asmaa_salon_booking_settings_{section}
 */
class Booking_Settings_Controller extends Base_Controller
{
    protected string $rest_base = 'booking/settings';

    public function register_routes(): void
    {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/all', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_all'],
                'permission_callback' => $this->permission_callback('asmaa_booking_settings_view'),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<section>[a-z\\-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_section'],
                'permission_callback' => $this->permission_callback('asmaa_booking_settings_view'),
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'save_section'],
                'permission_callback' => $this->permission_callback('asmaa_booking_settings_update'),
            ],
        ]);
    }

    public function get_all(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $data = [
            'general'        => $this->get_option('general', $this->default_general()),
            'url'            => $this->get_option('url', $this->default_url()),
            'calendar'       => $this->get_option('calendar', $this->default_calendar()),
            'company'        => $this->get_option('company', $this->default_company()),
            'customers'      => $this->get_option('customers', $this->default_customers()),
            'appointments'   => $this->get_option('appointments', $this->default_appointments()),
            'payments'       => $this->get_option('payments', $this->default_payments()),
            'appearance'     => $this->get_option('appearance', $this->default_appearance()),
            'business_hours' => $this->get_option('business-hours', $this->default_business_hours()),
            'holidays'       => $this->get_option('holidays', []),
        ];

        return $this->success_response($data);
    }

    public function get_section(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $section = (string) $request->get_param('section');

        $defaults = match ($section) {
            'general' => $this->default_general(),
            'url' => $this->default_url(),
            'calendar' => $this->default_calendar(),
            'company' => $this->default_company(),
            'customers' => $this->default_customers(),
            'appointments' => $this->default_appointments(),
            'payments' => $this->default_payments(),
            'appearance' => $this->default_appearance(),
            'business-hours' => $this->default_business_hours(),
            'holidays' => [],
            default => [],
        };

        return $this->success_response($this->get_option($section, $defaults));
    }

    public function save_section(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $section = (string) $request->get_param('section');
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $payload = [];
        }

        // Some tabs send { hours: [...] } etc.
        if ($section === 'business-hours' && isset($payload['hours']) && is_array($payload['hours'])) {
            $payload = $payload['hours'];
        }

        update_option($this->option_name($section), $payload, false);

        return $this->success_response($payload, 'Saved');
    }

    private function option_name(string $section): string
    {
        return 'asmaa_salon_booking_settings_' . str_replace('-', '_', $section);
    }

    private function get_option(string $section, array $default): array
    {
        $value = get_option($this->option_name($section), null);
        return is_array($value) ? $value : $default;
    }

    private function default_general(): array
    {
        return [
            'time_slot_length' => 15,
            'slot_as_duration' => false,
            'min_time_booking' => 0,
            'min_time_booking_unit' => 'hours',
            'min_time_cancel' => 0,
            'min_time_cancel_unit' => 'hours',
            'max_days_booking' => 365,
            'show_timeslots_client_timezone' => false,
            'allow_staff_edit_profile' => true,
            'prevent_caching' => true,
            'session_mode' => 'PHP',
            'prevent_session_locking' => false,
            'show_news_notifications' => true,
            'mail_gateway' => 'wp',
            'powered_by_bookly' => false,
            'data_on_delete' => "don't_delete",
        ];
    }

    private function default_url(): array
    {
        return [
            'booking_url' => '',
            'redirect_after_booking' => '',
        ];
    }

    private function default_calendar(): array
    {
        return [
            'first_day_of_week' => 1,
            'time_format' => '12',
            'date_format' => 'Y-m-d',
        ];
    }

    private function default_company(): array
    {
        return [
            'company_name' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_address' => '',
        ];
    }

    private function default_customers(): array
    {
        return [
            'require_name' => true,
            'require_email' => true,
            'require_phone' => false,
            'show_notes' => true,
        ];
    }

    private function default_appointments(): array
    {
        return [
            'default_status' => 'pending',
            'allow_cancel' => true,
        ];
    }

    private function default_payments(): array
    {
        return [
            'enable_payments' => false,
            'currency' => 'KWD',
            'payment_required' => false,
            'gateways' => [
                'cash' => true,
                'card' => true,
            ],
        ];
    }

    private function default_appearance(): array
    {
        return [
            'primary_color' => '#BBA07A',
            'secondary_color' => '#764ba2',
            'text_color' => '#1e293b',
            'background_color' => '#ffffff',
            'button_color' => '#BBA07A',
            'show_progress_tracker' => true,
            'progress_tracker_position' => 'top',
            'align_buttons_left' => false,
            'invert_datepicker_colors' => false,
            'required_employee' => false,
            'show_service_duration' => true,
            'show_service_price' => true,
            'show_category_info' => true,
            'show_service_info' => true,
            'show_staff_info' => true,
            'service_info_text' => '',
            'staff_info_text' => '',
            'labels' => [
                'next' => 'Next',
                'previous' => 'Previous',
                'save' => 'Save',
            ],
            'custom_css' => '',
            'custom_js' => '',
        ];
    }

    private function default_business_hours(): array
    {
        return [
            ['nameKey' => 'booking.daysOfWeek.sunday', 'enabled' => false, 'start' => '09:00', 'end' => '17:00'],
            ['nameKey' => 'booking.daysOfWeek.monday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ['nameKey' => 'booking.daysOfWeek.tuesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ['nameKey' => 'booking.daysOfWeek.wednesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ['nameKey' => 'booking.daysOfWeek.thursday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ['nameKey' => 'booking.daysOfWeek.friday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ['nameKey' => 'booking.daysOfWeek.saturday', 'enabled' => false, 'start' => '09:00', 'end' => '17:00'],
        ];
    }
}

