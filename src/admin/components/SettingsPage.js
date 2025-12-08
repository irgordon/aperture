import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const SettingsPage = () => {
    // Initialize with empty strings to prevent "false" flashing
    const [settings, setSettings] = useState({
        branding: { company_name: '', logo_url: '', support_email: '' },
        stripe: { public_key: '', secret_key: '' },
        google: { client_id: '', client_secret: '' },
        system: { sandbox_mode: 'yes' }
    });
    const [status, setStatus] = useState('');
    const [testStatus, setTestStatus] = useState(null);

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
                },
                google: {
                    client_id: clean(data.google.client_id),
                    client_secret: clean(data.google.client_secret)
                },
                system: {
                    sandbox_mode: clean(data.system?.sandbox_mode || 'yes')
                }
            });
        });
    }, []);

    const save = async () => {
        setStatus('saving');
        try {
            await apiFetch({ path: '/aperture/v1/settings', method: 'POST', data: settings });
            setStatus('saved');
            setTimeout(() => setStatus(''), 3000);
        } catch (e) {
            setStatus('error');
        }
    };

    const update = (section, field, value) => {
        setSettings(prev => ({
            ...prev,
            [section]: { ...prev[section], [field]: value }
        }));
    };

    const testStripe = async () => {
        setTestStatus({ msg: 'Testing...', type: 'info' });
        try {
            const res = await apiFetch({ 
                path: '/aperture/v1/settings/test-stripe', 
                method: 'POST', 
                data: { secret_key: settings.stripe.secret_key } 
            });
            setTestStatus({ msg: `✅ ${res.message} (${res.mode})`, type: 'success' });
        } catch (err) {
            setTestStatus({ msg: `❌ Failed: ${err.message}`, type: 'error' });
        }
    };

    return (
        <div className="ap-settings-container">
            <header>
                <h2>Configuration</h2>
                <div style={{display:'flex', gap:'10px', alignItems:'center'}}>
                    <div className="sandbox-toggle">
                         <label className="switch">
                            <input 
                                type="checkbox" 
                                checked={settings.system.sandbox_mode === 'yes'} 
                                onChange={e => update('system', 'sandbox_mode', e.target.checked ? 'yes' : 'no')}
                            />
                            <span className="slider round"></span>
                        </label>
                        <span className="label">
                            {settings.system.sandbox_mode === 'yes' ? 'SANDBOX MODE' : 'PRODUCTION MODE'}
                        </span>
                    </div>

                    <button className="btn-primary" onClick={save} disabled={status === 'saving'}>
                        {status === 'saving' ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
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
                    <div className="test-area" style={{marginTop:'15px', borderTop:'1px solid #eee', paddingTop:'15px'}}>
                        <button className="btn-secondary small" onClick={testStripe}>Test Connection</button>
                        {testStatus && (
                            <span className={`test-result ${testStatus.type}`} style={{marginLeft:'10px', fontSize:'13px'}}>
                                {testStatus.msg}
                            </span>
                        )}
                    </div>
                </div>

                <div className="ap-card">
                    <h3>Google Integration</h3>
                    <div className="form-group">
                        <label>Client ID</label>
                        <input 
                            type="text" 
                            value={settings.google.client_id} 
                            onChange={e => update('google', 'client_id', e.target.value)} 
                        />
                    </div>
                    <div className="form-group">
                        <label>Client Secret</label>
                        <input 
                            type="password" 
                            value={settings.google.client_secret} 
                            onChange={e => update('google', 'client_secret', e.target.value)} 
                        />
                    </div>
                </div>
            </div>
        </div>
    );
};
export default SettingsPage;
