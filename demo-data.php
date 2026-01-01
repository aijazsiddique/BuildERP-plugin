<?php
/**
 * BuildERP Demo Data Generator
 *
 * Creates sample data for testing and demonstration purposes.
 * Run this via WP-CLI: wp eval-file demo-data.php
 * Or include in a custom admin action.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate demo data for BuildERP.
 *
 * @param bool $silent Whether to suppress output.
 * @return array Summary of created items.
 */
function berp_generate_demo_data( $silent = false ) {
	$summary = array(
		'clients'    => 0,
		'sites'      => 0,
		'employees'  => 0,
		'attendance' => 0,
		'expenses'   => 0,
		'quotations' => 0,
		'invoices'   => 0,
	);

	// Demo Clients.
	$clients_data = array(
		array(
			'name'    => 'ABC Construction Ltd',
			'email'   => 'contact@abcconstruction.com',
			'phone'   => '+1 555-0101',
			'address' => '123 Builder Street, Construction City, CC 12345',
			'type'    => 'company',
		),
		array(
			'name'    => 'John Smith',
			'email'   => 'john.smith@email.com',
			'phone'   => '+1 555-0102',
			'address' => '456 Homeowner Lane, Residential Town, RT 67890',
			'type'    => 'individual',
		),
		array(
			'name'    => 'Metro Development Corp',
			'email'   => 'info@metrodev.com',
			'phone'   => '+1 555-0103',
			'address' => '789 Corporate Blvd, Business District, BD 11111',
			'type'    => 'company',
		),
		array(
			'name'    => 'Sarah Johnson',
			'email'   => 'sarah.j@email.com',
			'phone'   => '+1 555-0104',
			'address' => '321 Oak Avenue, Suburbia, SB 22222',
			'type'    => 'individual',
		),
		array(
			'name'    => 'GreenBuild Inc',
			'email'   => 'hello@greenbuild.com',
			'phone'   => '+1 555-0105',
			'address' => '555 Eco Park, Green City, GC 33333',
			'type'    => 'company',
		),
	);

	$client_ids = array();
	foreach ( $clients_data as $client ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_client',
				'post_title'  => $client['name'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_berp_client_email', $client['email'] );
			update_post_meta( $post_id, '_berp_client_phone', $client['phone'] );
			update_post_meta( $post_id, '_berp_client_address', $client['address'] );
			update_post_meta( $post_id, '_berp_client_type', $client['type'] );
			$client_ids[] = $post_id;
			++$summary['clients'];
		}
	}

	// Demo Sites/Projects.
	$sites_data = array(
		array(
			'name'    => 'Downtown Office Tower',
			'client'  => 0,
			'address' => '100 Main Street, Downtown',
			'status'  => 'in_progress',
			'start'   => gmdate( 'Y-m-d', strtotime( '-60 days' ) ),
			'end'     => gmdate( 'Y-m-d', strtotime( '+120 days' ) ),
			'budget'  => 2500000,
		),
		array(
			'name'    => 'Residential Complex Phase 1',
			'client'  => 1,
			'address' => '200 Suburb Road, Residential Area',
			'status'  => 'in_progress',
			'start'   => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
			'end'     => gmdate( 'Y-m-d', strtotime( '+90 days' ) ),
			'budget'  => 850000,
		),
		array(
			'name'    => 'Shopping Mall Renovation',
			'client'  => 2,
			'address' => '300 Commerce Ave, Shopping District',
			'status'  => 'planning',
			'start'   => gmdate( 'Y-m-d', strtotime( '+15 days' ) ),
			'end'     => gmdate( 'Y-m-d', strtotime( '+180 days' ) ),
			'budget'  => 1200000,
		),
		array(
			'name'    => 'Home Extension Project',
			'client'  => 3,
			'address' => '321 Oak Avenue, Suburbia',
			'status'  => 'completed',
			'start'   => gmdate( 'Y-m-d', strtotime( '-90 days' ) ),
			'end'     => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
			'budget'  => 75000,
		),
		array(
			'name'    => 'Eco-Friendly Office Building',
			'client'  => 4,
			'address' => '555 Eco Park, Green City',
			'status'  => 'planning',
			'start'   => gmdate( 'Y-m-d', strtotime( '+30 days' ) ),
			'end'     => gmdate( 'Y-m-d', strtotime( '+365 days' ) ),
			'budget'  => 3500000,
		),
	);

	$site_ids = array();
	foreach ( $sites_data as $site ) {
		$client_id = isset( $client_ids[ $site['client'] ] ) ? $client_ids[ $site['client'] ] : 0;

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_site',
				'post_title'  => $site['name'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_berp_client_id', $client_id );
			update_post_meta( $post_id, '_berp_site_address', $site['address'] );
			update_post_meta( $post_id, '_berp_site_status', $site['status'] );
			update_post_meta( $post_id, '_berp_start_date', $site['start'] );
			update_post_meta( $post_id, '_berp_end_date', $site['end'] );
			update_post_meta( $post_id, '_berp_budget', $site['budget'] );
			$site_ids[] = $post_id;
			++$summary['sites'];
		}
	}

	// Demo Employees.
	$employees_data = array(
		array(
			'name'   => 'Michael Rodriguez',
			'emp_id' => 'EMP001',
			'email'  => 'michael.r@demo.builderp.local',
			'phone'  => '+1 555-1001',
			'salary' => 4500,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-2 years' ) ),
		),
		array(
			'name'   => 'Emily Chen',
			'emp_id' => 'EMP002',
			'email'  => 'emily.c@demo.builderp.local',
			'phone'  => '+1 555-1002',
			'salary' => 3800,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-18 months' ) ),
		),
		array(
			'name'   => 'David Williams',
			'emp_id' => 'EMP003',
			'email'  => 'david.w@demo.builderp.local',
			'phone'  => '+1 555-1003',
			'salary' => 5200,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-3 years' ) ),
		),
		array(
			'name'   => 'Jessica Martinez',
			'emp_id' => 'EMP004',
			'email'  => 'jessica.m@demo.builderp.local',
			'phone'  => '+1 555-1004',
			'salary' => 3500,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-6 months' ) ),
		),
		array(
			'name'   => 'Robert Johnson',
			'emp_id' => 'EMP005',
			'email'  => 'robert.j@demo.builderp.local',
			'phone'  => '+1 555-1005',
			'salary' => 4000,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-1 year' ) ),
		),
		array(
			'name'   => 'Amanda Lee',
			'emp_id' => 'EMP006',
			'email'  => 'amanda.l@demo.builderp.local',
			'phone'  => '+1 555-1006',
			'salary' => 4200,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-14 months' ) ),
		),
		array(
			'name'   => 'Christopher Brown',
			'emp_id' => 'EMP007',
			'email'  => 'chris.b@demo.builderp.local',
			'phone'  => '+1 555-1007',
			'salary' => 3600,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-8 months' ) ),
		),
		array(
			'name'   => 'Sarah Davis',
			'emp_id' => 'EMP008',
			'email'  => 'sarah.d@demo.builderp.local',
			'phone'  => '+1 555-1008',
			'salary' => 4800,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-2.5 years' ) ),
		),
		array(
			'name'   => 'James Wilson',
			'emp_id' => 'EMP009',
			'email'  => 'james.w@demo.builderp.local',
			'phone'  => '+1 555-1009',
			'salary' => 3200,
			'status' => 'on_leave',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-10 months' ) ),
		),
		array(
			'name'   => 'Jennifer Taylor',
			'emp_id' => 'EMP010',
			'email'  => 'jennifer.t@demo.builderp.local',
			'phone'  => '+1 555-1010',
			'salary' => 3900,
			'status' => 'active',
			'hire'   => gmdate( 'Y-m-d', strtotime( '-1.5 years' ) ),
		),
	);

	$employee_ids = array();
	foreach ( $employees_data as $emp ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_employee',
				'post_title'  => $emp['name'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_berp_employee_id', $emp['emp_id'] );
			update_post_meta( $post_id, '_berp_email', $emp['email'] );
			update_post_meta( $post_id, '_berp_phone', $emp['phone'] );
			update_post_meta( $post_id, '_berp_basic_salary', $emp['salary'] );
			update_post_meta( $post_id, '_berp_status', $emp['status'] );
			update_post_meta( $post_id, '_berp_hire_date', $emp['hire'] );
			update_post_meta( $post_id, '_berp_account_balance', 0 );
			$employee_ids[] = $post_id;
			++$summary['employees'];
		}
	}

	// Demo Attendance (last 30 days for active employees).
	$active_employees = array_slice( $employee_ids, 0, 8 ); // First 8 are active.
	$active_sites     = array_slice( $site_ids, 0, 2 );     // First 2 are in_progress.

	for ( $day = 30; $day >= 1; $day-- ) {
		$date        = gmdate( 'Y-m-d', strtotime( "-{$day} days" ) );
		$day_of_week = gmdate( 'N', strtotime( $date ) );

		// Skip weekends.
		if ( $day_of_week >= 6 ) {
			continue;
		}

		foreach ( $active_employees as $emp_id ) {
			// 90% attendance rate.
			if ( wp_rand( 1, 100 ) > 90 ) {
				continue;
			}

			$site_id  = $active_sites[ array_rand( $active_sites ) ];
			$overtime = ( wp_rand( 1, 100 ) <= 20 ) ? wp_rand( 1, 3 ) : 0;

			$att_id = wp_insert_post(
				array(
					'post_type'   => 'berp_attendance',
					'post_title'  => sprintf( 'Attendance - %s', $date ),
					'post_status' => 'publish',
				)
			);

			if ( ! is_wp_error( $att_id ) ) {
				update_post_meta( $att_id, '_berp_employee_id', $emp_id );
				update_post_meta( $att_id, '_berp_date', $date );
				update_post_meta( $att_id, '_berp_site_id', $site_id );
				update_post_meta( $att_id, '_berp_overtime_hours', $overtime );
				update_post_meta( $att_id, '_berp_status', 'present' );
				++$summary['attendance'];
			}
		}
	}

	// Demo Expenses.
	$expense_categories = array( 'materials', 'equipment', 'labor', 'transport', 'utilities', 'misc' );
	$payment_methods    = array( 'cash', 'bank_transfer', 'credit_card', 'check' );

	$expenses_data = array(
		array(
			'title'  => 'Cement and Concrete Supply',
			'amount' => 15000,
			'cat'    => 'materials',
		),
		array(
			'title'  => 'Steel Reinforcement Bars',
			'amount' => 8500,
			'cat'    => 'materials',
		),
		array(
			'title'  => 'Crane Rental - Weekly',
			'amount' => 3500,
			'cat'    => 'equipment',
		),
		array(
			'title'  => 'Excavator Fuel',
			'amount' => 1200,
			'cat'    => 'transport',
		),
		array(
			'title'  => 'Site Electricity Bill',
			'amount' => 850,
			'cat'    => 'utilities',
		),
		array(
			'title'  => 'Safety Equipment Purchase',
			'amount' => 2200,
			'cat'    => 'equipment',
		),
		array(
			'title'  => 'Lumber and Wood Materials',
			'amount' => 4500,
			'cat'    => 'materials',
		),
		array(
			'title'  => 'Plumbing Supplies',
			'amount' => 3200,
			'cat'    => 'materials',
		),
		array(
			'title'  => 'Electrical Wiring',
			'amount' => 2800,
			'cat'    => 'materials',
		),
		array(
			'title'  => 'Contractor Payment - Tile Work',
			'amount' => 5000,
			'cat'    => 'labor',
		),
	);

	foreach ( $expenses_data as $index => $exp ) {
		$date    = gmdate( 'Y-m-d', strtotime( '-' . ( $index * 3 ) . ' days' ) );
		$site_id = $site_ids[ array_rand( $site_ids ) ];

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_expense',
				'post_title'  => $exp['title'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_berp_expense_date', $date );
			update_post_meta( $post_id, '_berp_expense_amount', $exp['amount'] );
			update_post_meta( $post_id, '_berp_expense_category', $exp['cat'] );
			update_post_meta( $post_id, '_berp_payment_method', $payment_methods[ array_rand( $payment_methods ) ] );
			update_post_meta( $post_id, '_berp_site_id', $site_id );
			++$summary['expenses'];
		}
	}

	// Demo Quotations.
	$quotations_data = array(
		array(
			'client' => 0,
			'title'  => 'Office Building Foundation Work',
			'items'  => array(
				array(
					'desc' => 'Site Preparation',
					'qty'  => 1,
					'rate' => 25000,
				),
				array(
					'desc' => 'Foundation Excavation',
					'qty'  => 1,
					'rate' => 45000,
				),
				array(
					'desc' => 'Concrete Foundation',
					'qty'  => 500,
					'rate' => 150,
				),
				array(
					'desc' => 'Steel Reinforcement',
					'qty'  => 200,
					'rate' => 85,
				),
			),
			'status' => 'accepted',
		),
		array(
			'client' => 1,
			'title'  => 'Residential Kitchen Renovation',
			'items'  => array(
				array(
					'desc' => 'Demolition Work',
					'qty'  => 1,
					'rate' => 3000,
				),
				array(
					'desc' => 'Plumbing Installation',
					'qty'  => 1,
					'rate' => 5500,
				),
				array(
					'desc' => 'Electrical Work',
					'qty'  => 1,
					'rate' => 4200,
				),
				array(
					'desc' => 'Cabinetry Installation',
					'qty'  => 8,
					'rate' => 850,
				),
				array(
					'desc' => 'Countertop Installation',
					'qty'  => 15,
					'rate' => 120,
				),
			),
			'status' => 'sent',
		),
		array(
			'client' => 2,
			'title'  => 'Mall Interior Renovation Phase 1',
			'items'  => array(
				array(
					'desc' => 'Interior Demolition',
					'qty'  => 1,
					'rate' => 35000,
				),
				array(
					'desc' => 'HVAC System Upgrade',
					'qty'  => 1,
					'rate' => 125000,
				),
				array(
					'desc' => 'Flooring Installation',
					'qty'  => 5000,
					'rate' => 45,
				),
				array(
					'desc' => 'Lighting System',
					'qty'  => 200,
					'rate' => 350,
				),
			),
			'status' => 'draft',
		),
	);

	$quotation_ids = array();
	foreach ( $quotations_data as $index => $quot ) {
		$client_id = isset( $client_ids[ $quot['client'] ] ) ? $client_ids[ $quot['client'] ] : 0;
		$date      = gmdate( 'Y-m-d', strtotime( '-' . ( ( $index + 1 ) * 10 ) . ' days' ) );

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_quotation',
				'post_title'  => $quot['title'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			// Calculate totals.
			$subtotal = 0;
			foreach ( $quot['items'] as $item ) {
				$subtotal += $item['qty'] * $item['rate'];
			}
			$tax   = $subtotal * 0.1; // 10% tax.
			$total = $subtotal + $tax;

			update_post_meta( $post_id, '_berp_client_id', $client_id );
			update_post_meta( $post_id, '_berp_quotation_date', $date );
			update_post_meta( $post_id, '_berp_valid_until', gmdate( 'Y-m-d', strtotime( $date . ' +30 days' ) ) );
			update_post_meta( $post_id, '_berp_status', $quot['status'] );
			update_post_meta( $post_id, '_berp_line_items', $quot['items'] );
			update_post_meta( $post_id, '_berp_subtotal', $subtotal );
			update_post_meta( $post_id, '_berp_tax_rate', 10 );
			update_post_meta( $post_id, '_berp_tax_amount', $tax );
			update_post_meta( $post_id, '_berp_grand_total', $total );

			// Generate quotation number.
			update_post_meta( $post_id, '_berp_quotation_number', 'QT-' . gmdate( 'Y' ) . '-' . str_pad( $index + 1, 4, '0', STR_PAD_LEFT ) );

			$quotation_ids[] = $post_id;
			++$summary['quotations'];
		}
	}

	// Demo Invoices.
	$invoices_data = array(
		array(
			'client' => 0,
			'title'  => 'Invoice - Foundation Work Progress 1',
			'items'  => array(
				array(
					'desc' => 'Site Preparation - Complete',
					'qty'  => 1,
					'rate' => 25000,
				),
				array(
					'desc' => 'Foundation Excavation - 50%',
					'qty'  => 0.5,
					'rate' => 45000,
				),
			),
			'status' => 'paid',
			'paid'   => 47500,
		),
		array(
			'client' => 3,
			'title'  => 'Invoice - Home Extension Final',
			'items'  => array(
				array(
					'desc' => 'Extension Construction',
					'qty'  => 1,
					'rate' => 55000,
				),
				array(
					'desc' => 'Interior Finishing',
					'qty'  => 1,
					'rate' => 15000,
				),
				array(
					'desc' => 'Final Inspection & Cleanup',
					'qty'  => 1,
					'rate' => 2500,
				),
			),
			'status' => 'paid',
			'paid'   => 79750,
		),
		array(
			'client' => 1,
			'title'  => 'Invoice - Kitchen Renovation Deposit',
			'items'  => array(
				array(
					'desc' => 'Project Deposit (30%)',
					'qty'  => 1,
					'rate' => 6825,
				),
			),
			'status' => 'sent',
			'paid'   => 0,
		),
	);

	foreach ( $invoices_data as $index => $inv ) {
		$client_id = isset( $client_ids[ $inv['client'] ] ) ? $client_ids[ $inv['client'] ] : 0;
		$date      = gmdate( 'Y-m-d', strtotime( '-' . ( ( $index + 1 ) * 7 ) . ' days' ) );

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'berp_invoice',
				'post_title'  => $inv['title'],
				'post_status' => 'publish',
			)
		);

		if ( ! is_wp_error( $post_id ) ) {
			// Calculate totals.
			$subtotal = 0;
			foreach ( $inv['items'] as $item ) {
				$subtotal += $item['qty'] * $item['rate'];
			}
			$tax   = $subtotal * 0.1;
			$total = $subtotal + $tax;

			update_post_meta( $post_id, '_berp_client_id', $client_id );
			update_post_meta( $post_id, '_berp_invoice_date', $date );
			update_post_meta( $post_id, '_berp_due_date', gmdate( 'Y-m-d', strtotime( $date . ' +30 days' ) ) );
			update_post_meta( $post_id, '_berp_status', $inv['status'] );
			update_post_meta( $post_id, '_berp_line_items', $inv['items'] );
			update_post_meta( $post_id, '_berp_subtotal', $subtotal );
			update_post_meta( $post_id, '_berp_tax_rate', 10 );
			update_post_meta( $post_id, '_berp_tax_amount', $tax );
			update_post_meta( $post_id, '_berp_grand_total', $total );
			update_post_meta( $post_id, '_berp_amount_paid', $inv['paid'] );
			update_post_meta( $post_id, '_berp_amount_due', $total - $inv['paid'] );

			// Generate invoice number.
			update_post_meta( $post_id, '_berp_invoice_number', 'INV-' . gmdate( 'Y' ) . '-' . str_pad( $index + 1, 4, '0', STR_PAD_LEFT ) );

			++$summary['invoices'];
		}
	}

	if ( ! $silent ) {
		echo "Demo Data Generation Complete!\n";
		echo "==============================\n";
		echo "Clients created:    {$summary['clients']}\n";
		echo "Sites created:      {$summary['sites']}\n";
		echo "Employees created:  {$summary['employees']}\n";
		echo "Attendance records: {$summary['attendance']}\n";
		echo "Expenses created:   {$summary['expenses']}\n";
		echo "Quotations created: {$summary['quotations']}\n";
		echo "Invoices created:   {$summary['invoices']}\n";
	}

	return $summary;
}

/**
 * Clear all demo data.
 *
 * @param bool $silent Whether to suppress output.
 * @return array Summary of deleted items.
 */
function berp_clear_demo_data( $silent = false ) {
	$post_types = array(
		'berp_client',
		'berp_site',
		'berp_employee',
		'berp_attendance',
		'berp_expense',
		'berp_quotation',
		'berp_invoice',
		'berp_payroll',
	);

	$summary = array();

	foreach ( $post_types as $post_type ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		$count = 0;
		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
			++$count;
		}

		$summary[ $post_type ] = $count;
	}

	if ( ! $silent ) {
		echo "Demo Data Cleared!\n";
		echo "==================\n";
		foreach ( $summary as $type => $count ) {
			$label = str_replace( 'berp_', '', $type );
			echo "{$label}: {$count} deleted\n";
		}
	}

	return $summary;
}

// If run via WP-CLI.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'berp demo generate',
		function () {
			berp_generate_demo_data();
		}
	);

	WP_CLI::add_command(
		'berp demo clear',
		function () {
			berp_clear_demo_data();
		}
	);
}
