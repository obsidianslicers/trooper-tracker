import { howTo } from './support/director.js';

howTo('01-getting-started-and-registration', 'Getting Started & Registration', {}, async ({ page, director: d }) => {
    await d.goto('/');
    await d.action();

    d.caption('New to the tracker? Press Sign Up.');
    await d.pause(1500);
    await d.click(page.getByRole('link', { name: /sign up/i }).first(), { pause: 1500 });

    d.caption('Sign up with your email, or with a login your garrison offers.');
    await d.pause(1200);
    await d.click(page.getByRole('link', { name: 'Sign Up with Email' }), { pause: 1200 });

    d.caption('Enter your email and a password.');
    await d.type(page.getByLabel('Email'), 'new.recruit@example.org');
    await d.type(page.getByLabel('Password'), 'a-strong-password');

    d.caption('Your legal name stays private; your display name is shown on rosters.');
    await d.type(page.getByLabel('Legal Name'), 'Robin Hayes');
    await d.type(page.getByLabel('Display Name'), 'Robin Hayes');

    d.caption('Choose your account type.');
    await d.select(page.getByLabel('Membership Role'), { label: 'Member' });

    d.caption('Pick your club, enter your ID, and choose your garrison and squad.');
    await d.click(page.getByLabel('501st Legion'));
    await d.type(page.getByLabel('TKID'), '95123');
    await d.select(page.getByLabel('Region/Garrison'), { label: 'Sunrise Garrison' });
    await d.select(page.getByLabel('Unit/Squad'), { label: 'Harbor Squad' });

    d.caption('Press Register. Command Staff review your application, and you get an email when it is approved.');
    await d.click(page.getByRole('button', { name: 'Register' }), { pause: 3500 });
});
