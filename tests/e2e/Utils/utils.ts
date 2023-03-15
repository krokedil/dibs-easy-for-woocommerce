import { APIRequestContext, request } from "@playwright/test";

const {
	NETSEASY_API_TEST_KEY,
	NETSEASY_API_TEST_CHECKOUT_KEY,
} = process.env;

export const GetNetseasyApiClient = async (): Promise<APIRequestContext> => {
	return await request.newContext({
		baseURL: `https://api.playground.klarna.com/payments/v1/`,
		extraHTTPHeaders: {
			Authorization: `Basic ${Buffer.from(
				`${NETSEASY_API_TEST_KEY ?? 'admin'}:${NETSEASY_API_TEST_CHECKOUT_KEY ?? 'password'}`
			).toString('base64')}`,
		},
	});
}

export const SetNetseasySettings = async (wcApiClient: APIRequestContext) => {
	// Set api credentials and enable the gateway.
	if (NETSEASY_API_TEST_KEY) {
		const settings = {
			enabled: true,
			settings: {
				test_mode: "yes",
				debug_mode: "yes",
				dibs_test_key: NETSEASY_API_TEST_KEY,
				dibs_test_checkout_key: NETSEASY_API_TEST_CHECKOUT_KEY,
			}
		};

		// Update settings.
		await wcApiClient.post('payment_gateways/dibs_easy', { data: settings });
	}
}