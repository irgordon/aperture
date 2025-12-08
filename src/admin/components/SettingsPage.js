import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const SettingsPage = () => {
    // Initialize with empty strings to prevent "false" flashing
    const [settings, setSettings] = useState({
        branding: { company_name: '', logo_url: '', support_email: '' },
        stripe: { public_key: '', secret_key: '' }
    });
    const [status, setStatus] = useState('');

    useEffect(() => {
        apiFetch({ path: '/aperture/v1/settings' }).then(data => {
            // FIX: Convert database 'false' or null to empty string
            const clean = (val) => (val === false || val === null ? '' : val);
            
            setSettings({
                branding: {
                    company_name: clean(data.branding.company_name),
                    logo_url: clean(data.branding.logo_url),
                    support_email: clean(data.branding.support_email)
                },
                stripe: {
                    public_key: clean(data.stripe.public_key),
                    secret_key: clean(data.stripe.secret_key)
                }
            });
        });
    }, []);

    const save = async () => {
        setStatus('saving');
        try {
            await apiFetch({ path: '/aperture/v1/settings', method: 'POST', data: settings });
            setStatus('saved');
            // Clear success message after 3 seconds
            setTimeout(() => setStatus(''), 3000);
        } catch (e) {
            setStatus('error');
        }
    };

    // Helper to update nested state
    const update = (section, field, value) => {
        setSettings(prev => ({
            ...prev,
            [section]: { ...prev[section], [field]: value }
        }));
    };

    return (
        <div className="ap-settings-container">
            <header>
                <h2>Configuration</h2>
                <button className="btn-primary" onClick={save} disabled={status === 'saving'}>
                    {status === 'saving' ? 'Saving...' : 'Save Changes'}
                </button>
            </header>

            {status === 'saved' && <div className="ap-toast">Settings Saved Successfully</div>}

            <div className="ap-grid">
                <div className="ap-card">
                    <h3>Branding</h3>
                    <div className="form-group">
                        <label>Company Name</label>
                        <input 
                            type="text" 
                            value={settings.branding.company_name} 
                            onChange={e => update('branding', 'company_name', e.target.value)} 
                        />
                    </div>
                    <div className="form-group">
                        <label>Logo URL</label>
                        <input 
                            type="text" 
                            value={settings.branding.logo_url} 
                            onChange={e => update('branding', 'logo_url', e.target.value)} 
                        />
                    </div>
                    <div className="form-group">
                        <label>Support Email</label>
                        <input 
                            type="email" 
                            value={settings.branding.support_email} 
                            onChange={e => update('branding', 'support_email', e.target.value)} 
                        />
                    </div>
                </div>

                <div className="ap-card">
                    <h3>Stripe Integration</h3>
                    <div className="form-group">
                        <label>Publishable Key</label>
                        <input 
                            className="code"
                            type="text" 
                            value={settings.stripe.public_key} 
                            onChange={e => update('stripe', 'public_key', e.target.value)} 
                        />
                    </div>
                    <div className="form-group">
                        <label>Secret Key</label>
                        <input 
                            className="code"
                            type="password" 
                            value={settings.stripe.secret_key} 
                            onChange={e => update('stripe', 'secret_key', e.target.value)} 
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};
export default SettingsPage;
