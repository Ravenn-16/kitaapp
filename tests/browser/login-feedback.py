"""Run against a local KITA server; intercept auth requests, never use real users.
Set PYTHONPATH to the directory containing Playwright if not installed globally.
"""
import json
import os
from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch(channel='chrome', headless=True)
    page = browser.new_page()
    errors = []
    page.on('pageerror', lambda error: errors.append(str(error)))
    pending = []
    page.route('**/login', lambda route: pending.append(route))
    page.goto(os.environ.get('KITA_TEST_URL', 'http://127.0.0.1:8766'), wait_until='networkidle')
    page.get_by_role('button', name='Sign In', exact=True).click()
    page.get_by_text('Enter a valid email address and password.', exact=True).wait_for()
    page.locator('#login-email').fill('probe@example.com')
    page.locator('#login-password').fill('NotARealPassword123')
    page.get_by_role('button', name='Sign In', exact=True).click()
    busy = page.get_by_role('button', name='Signing in...', exact=True)
    busy.wait_for()
    assert busy.is_disabled()
    page.wait_for_timeout(250)
    assert len(pending) == 1
    pending.pop().fulfill(status=503, content_type='application/json', body=json.dumps({'message': 'Email delivery unavailable.'}))
    page.get_by_text('Email delivery unavailable.', exact=True).wait_for()
    assert page.get_by_role('button', name='Sign In', exact=True).is_enabled()
    page.locator('#login-password').fill('NotARealPassword123')
    page.locator('#login-password').press('Enter')
    busy.wait_for()
    page.wait_for_timeout(250)
    assert len(pending) == 1
    pending.pop().fulfill(content_type='application/json', body=json.dumps({'otp_required': True, 'email': 'probe@example.com'}))
    page.get_by_role('button', name='Verify OTP', exact=True).wait_for()
    page.clock.install()
    page.route('**/otp/verify', lambda route: pending.append(route))
    page.get_by_label('Six-digit verification code').fill('123456')
    page.get_by_role('button', name='Verify OTP', exact=True).click()
    page.get_by_role('button', name='Verifying...', exact=True).wait_for()
    page.clock.fast_forward(31000)
    page.get_by_text('Sign-in timed out.', exact=False).wait_for()
    assert page.get_by_role('button', name='Verify OTP', exact=True).is_enabled()
    for route in pending:
        route.abort()
    assert not errors, errors
    print('PASS: validation, click/Enter submission, single request, loading/disabled state, mail error recovery, OTP transition, timeout recovery')
    browser.close()
