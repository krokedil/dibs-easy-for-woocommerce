import {test} from '@playwright/test';

test.use({ storageState: process.env.ADMINSTATE });
test('My first test', async({ page }) => {
    await page.goto('/shop');
    
});