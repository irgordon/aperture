import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import TemplateEditor from './TemplateEditor';

const SettingsPage = () => {
    const [activeTab, setActiveTab] = useState('branding');
    const [settings, setSettings] = useState({
        branding: { company_name: '', logo_url: '', support_email: '' },
        stripe: { public_key: '', secret_key: '' },
        google: { client_id: '', client_secret: '' },
        system: { sandbox_mode: 'yes' }
    });
    const [status, setStatus] = useState('');

    useEffect(() => {
        apiFetch({ path: '/aperture/v1/settings' }).then(data => {
            const clean = (val) => (val === false || val === null ? '' : val);
            setSettings({
                branding: { company_name: clean(data.branding.company_name), logo_url: clean(data.branding.logo_url), support_email: clean(data.branding.support_email) },
                stripe: { public_key: clean(data.stripe.public_key), secret_key: clean(data.stripe.secret_key) },
                google: { client_id: clean(data.google.client_id), client_secret: clean(data.google.client_secret) },
                system: { sandbox_mode: clean(data.system?.sandbox_mode || 'yes') }
            });
        });
    }, []);

    const save = async () => {
        setStatus('saving');
        await apiFetch({ path: '/aperture/v1/settings', method: 'POST', data: settings });
        setStatus('saved'); setTimeout(() => setStatus(''), 2000);
    };

    const update = (sec, f, v) => setSettings(p => ({...p, [sec]: {...p[sec], [f]: v}}));

    return (
        <div className="ap-settings-container">
            <header>
                <h2>Configuration</h2>
                <div style={{display:'flex', gap:'10px'}}>
                     {activeTab !== 'email' && <button className="btn-primary" onClick={save}>{status === 'saving' ? 'Saving...' : 'Save Changes'}</button>}
                </div>
            </header>
            <div className="ap-layout" style={{margin:0, background:'transparent'}}>
                <nav className="ap-nav" style={{height:'auto', minHeight:'300px'}}>
                    <button onClick={()=>setActiveTab('branding')} className={activeTab==='branding'?'active':''}>General</button>
                    <button onClick={()=>setActiveTab('email')} className={activeTab==='email'?'active':''}>Email Templates</button>
                </nav>
                <main style={{padding:'0 0 0 30px'}}>
                    {activeTab === 'branding' && (
                        <div className="ap-grid">
                            <div className="ap-card">
                                <h3>Branding</h3>
                                <input value={settings.branding.company_name} onChange={e=>update('branding','company_name',e.target.value)} placeholder="Company Name" style={{marginBottom:'10px', width:'100%'}}/>
                                <input value={settings.branding.logo_url} onChange={e=>update('branding','logo_url',e.target.value)} placeholder="Logo URL" style={{marginBottom:'10px', width:'100%'}}/>
                                <input value={settings.branding.support_email} onChange={e=>update('branding','support_email',e.target.value)} placeholder="Support Email" style={{marginBottom:'10px', width:'100%'}}/>
                            </div>
                            <div className="ap-card">
                                <h3>Stripe</h3>
                                <input value={settings.stripe.public_key} onChange={e=>update('stripe','public_key',e.target.value)} placeholder="Public Key" style={{marginBottom:'10px', width:'100%'}}/>
                                <input value={settings.stripe.secret_key} onChange={e=>update('stripe','secret_key',e.target.value)} placeholder="Secret Key" type="password" style={{marginBottom:'10px', width:'100%'}}/>
                            </div>
                             <div className="ap-card">
                                <h3>Google</h3>
                                <input value={settings.google.client_id} onChange={e=>update('google','client_id',e.target.value)} placeholder="Client ID" style={{marginBottom:'10px', width:'100%'}}/>
                                <input value={settings.google.client_secret} onChange={e=>update('google','client_secret',e.target.value)} placeholder="Client Secret" type="password" style={{marginBottom:'10px', width:'100%'}}/>
                            </div>
                        </div>
                    )}
                    {activeTab === 'email' && <TemplateEditor />}
                </main>
            </div>
        </div>
    );
};
export default SettingsPage;
