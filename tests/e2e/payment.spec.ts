import { execFileSync } from 'node:child_process';
import { expect, test, type Page } from '@playwright/test';

type Fixture = {
	formId: number;
	payPath: string;
};

type Entry = {
	paymentStatus: string;
	transactionId: string;
};

type Card = {
	number: string;
	securityCode: string;
};

const fixture: Fixture = JSON.parse( process.env.E2E_FIXTURE ?? '{}' );

const visa: Card = { number: '4929000000006', securityCode: '123' };

/**
 * Fills in and submits the payment form with fake customer details.
 */
async function submitPaymentForm( page: Page ): Promise< void > {
	await page.goto( fixture.payPath );
	await page.locator( '[name="input_1.3"]' ).fill( 'Ada' );
	await page.locator( '[name="input_1.6"]' ).fill( 'Lovelace' );
	await page.locator( '[name="input_2"]' ).fill( 'ada@example.org' );
	await page.locator( '[name="input_3.1"]' ).fill( '88' );
	await page.locator( '[name="input_3.3"]' ).fill( 'London' );
	await page.locator( '[name="input_3.5"]' ).fill( '412' );
	await page.locator( '[name="input_3.6"]' ).selectOption( 'United Kingdom' );
	await page.locator( `#gform_submit_button_${ fixture.formId }` ).click();
	await page.waitForURL( /sandbox\.opayo\.eu\.elavon\.com\/gateway\/service\/cardselection/ );
}

/**
 * Pays on Opayo's hosted pages.
 * The card details must be typed, as the page rejects values set directly from script.
 */
async function payWithCard( page: Page, card: Card ): Promise< void > {
	await page.getByRole( 'img', { name: 'Visa', exact: true } ).click();
	await page.waitForURL( /gateway\/service\/carddetails/ );
	await page.locator( '[name="cardnumber"]' ).pressSequentially( card.number );
	await page.locator( '[name="expirymonth"]' ).selectOption( '12' );
	await page.locator( '[name="expiryyear"]' ).selectOption( String( new Date().getFullYear() + 2 ) );
	await page.locator( '[name="securitycode"]' ).pressSequentially( card.securityCode );
	await page.getByRole( 'img', { name: 'Proceed' } ).click();
	await page.waitForURL( /gateway\/service\/cardconfirmation/ );
	await page.getByRole( 'img', { name: 'Pay Now' } ).click();
}

/**
 * Reads the form's latest entry through WP-CLI in the wp-env container.
 */
function latestEntry(): Entry {
	const php = `$entries = GFAPI::get_entries(${ fixture.formId }, [], ['key' => 'id', 'direction' => 'DESC'], ['page_size' => 1]);`
		+ ` $entry = $entries[0] ?? [];`
		+ ` echo json_encode(['paymentStatus' => (string) rgar($entry, 'payment_status'), 'transactionId' => (string) rgar($entry, 'transaction_id')]);`;

	try {
		return JSON.parse( execFileSync( 'npx', [ 'wp-env', 'run', 'cli', 'wp', 'eval', php ], {
			cwd: __dirname,
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		} ) );
	} catch ( error ) {
		const stderr = ( error as { stderr?: string } ).stderr ?? '';
		throw new Error( `Unable to read the latest entry: ${ stderr }` );
	}
}

test( 'a successful card payment marks the entry paid and shows the confirmation', async ( { page } ) => {
	await submitPaymentForm( page );
	await payWithCard( page, visa );

	await expect( page.getByText( 'Thank you, your payment was received.' ) ).toBeVisible();
	expect( page.url() ).toContain( fixture.payPath );
	const entry = latestEntry();
	expect( entry.paymentStatus ).toBe( 'Paid' );
	expect( entry.transactionId ).not.toBe( '' );
} );
