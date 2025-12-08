import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const SettingsPage = () => {
    const [settings, setSettings] = useState(null);
    const [status, setStatus] = useState('');

    useEffect(() => {
        apiFetch({ path: '/aperture/v1/settings' }).then(setSettings);
    }, []);

    const save = async () => {
        setStatus('saving');
        await apiFetch({ path: '/aperture/v1/settings', method: 'POST', data: settings });
        setStatus('saved');
    };

    if (!settings) return <div>Loading...</div>;

    return (
        <div className="ap-settings">
            <h2>Settings</h2>
            <div className="card">
                <h3>Branding</h3>
                <input type="text" value={settings.branding.company_name} onChange={e => setSettings({...settings, branding: {...settings.branding, company_name: e.target.value}})} placeholder="Company Name" />
                <input type="text" value={settings.branding.logo_url} onChange={e => setSettings({...settings, branding: {...settings.branding, logo_url: e.target.value}})} placeholder="Logo URL" />
                <input type="text" value={settings.branding.support_email} onChange={e => setSettings({...settings, branding: {...settings.branding, support_email: e.target.value}})} placeholder="Support Email" />
            </div>
            <div className="card">
                <h3>Stripe</h3>
                <input type="text" value={settings.stripe.public_key} onChange={e => setSettings({...settings, stripe: {...settings.stripe, public_key: e.target.value}})} placeholder="Public Key" />
                <input type="password" value={settings.stripe.secret_key} onChange={e => setSettings({...settings, stripe: {...settings.stripe, secret_key: e.target.value}})} placeholder="Secret Key" />
            </div>
            <button onClick={save} disabled={status === 'saving'}>{status === 'saving' ? 'Saving...' : 'Save Changes'}</button>
        </div>
    );
};
export default SettingsPage;
