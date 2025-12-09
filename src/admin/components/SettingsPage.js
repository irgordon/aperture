import { useState, useEffect, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';
import TemplateEditor from './TemplateEditor';

const SettingsPage = () => {
    const [activeTab, setActiveTab] = useState('config');
    const [settings, setSettings] = useState(null);
    const [status, setStatus] = useState('');
    const fileInput = useRef(null);

    // Sanitization Helper: Converts null/false to empty string
    const clean = (val) => (val === false || val === null || val === undefined) ? '' : val;

    useEffect(() => {
        apiFetch({ path: '/aperture/v1/settings' }).then(data => {
            const s = {
                branding: { 
                    company_name: clean(data.branding.company_name), 
                    logo_url: clean(data.branding.logo_url), 
                    support_email: clean(data.branding.support_email), 
                    address: clean(data.branding.address), 
                    phone: clean(data.branding.phone) 
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
            };
            setSettings(s);
        });
    }, []);

    if (!settings) return <div>Loading...</div>;

    const save = async () => {
        setStatus('saving');
        await apiFetch({ path: '/aperture/v1/settings', method: 'POST', data: settings });
        setStatus('saved'); setTimeout(() => setStatus(''), 2000);
    };

    const update = (sec, f, v) => setSettings(p => ({...p, [sec]: {...p[sec], [f]: v}}));

    const handleImport = async (e) => {
        if (!confirm("WARNING: Overwrite existing data?")) return;
        const file = e.target.files[0];
        const formData = new FormData();
        formData.append('file', file);
        try { await apiFetch({ path: '/aperture/v1/import', method: 'POST', body: formData }); alert('Import Complete!'); } catch(err) { alert('Import Failed'); }
    };

    return (
        <div className="ap-settings-container">
            <header>
                <h2>System Settings</h2>
                {activeTab !== 'email' && activeTab !== 'data' && <button className="btn-primary" onClick={save} disabled={status==='saving'}>{status==='saving'?'Saving...':'Save Changes'}</button>}
            </header>
            <div className="ap-tabs-layout">
                <nav className="ap-side-nav">
                    <button onClick={()=>setActiveTab('config')} className={activeTab==='config'?'active':''}>Configuration</button>
                    <button onClick={()=>setActiveTab('integrations')} className={activeTab==='integrations'?'active':''}>Integrations</button>
                    <button onClick={()=>setActiveTab('email')} className={activeTab==='email'?'active':''}>Email Templates</button>
                    <button onClick={()=>setActiveTab('data')} className={activeTab==='data'?'active':''}>Import / Export</button>
                </nav>
                <main className="ap-tab-content">
                    {activeTab === 'config' && (
                        <div className="ap-card fade-in">
                            <h3>Business Branding</h3>
                            <div className="form-group"><label>Company Name</label><input value={settings.branding.company_name} onChange={e=>update('branding','company_name',e.target.value)} /></div>
                            <div className="form-group"><label>Logo URL</label><input value={settings.branding.logo_url} onChange={e=>update('branding','logo_url',e.target.value)} /></div>
                            <div className="form-group"><label>Support Email</label><input value={settings.branding.support_email} onChange={e=>update('branding','support_email',e.target.value)} /></div>
                            <div className="form-group"><label>Address</label><textarea value={settings.branding.address} onChange={e=>update('branding','address',e.target.value)} /></div>
                        </div>
                    )}
                    {activeTab === 'integrations' && (
                        <div className="ap-grid-2">
                            <div className="ap-card"><h3>Stripe</h3><input className="code" value={settings.stripe.public_key} onChange={e=>update('stripe','public_key',e.target.value)} /><input className="code" type="password" value={settings.stripe.secret_key} onChange={e=>update('stripe','secret_key',e.target.value)} /></div>
                            <div className="ap-card"><h3>Google</h3><input className="code" value={settings.google.client_id} onChange={e=>update('google','client_id',e.target.value)} /><input className="code" type="password" value={settings.google.client_secret} onChange={e=>update('google','client_secret',e.target.value)} /></div>
                        </div>
                    )}
                    {activeTab === 'email' && <TemplateEditor />}
                    {activeTab === 'data' && (
                        <div className="ap-card fade-in">
                            <h3>Data Management</h3>
                            <div style={{marginBottom:'20px'}}><button className="btn-secondary" onClick={() => window.open(window.apertureProSettings.root + 'aperture/v1/export?_wpnonce='+window.apertureProSettings.nonce)}>Download Backup</button></div>
                            <div><input type="file" ref={fileInput} style={{display:'none'}} accept=".zip" onChange={handleImport} /><button className="btn-danger" onClick={() => fileInput.current.click()}>Restore Data</button></div>
                        </div>
                    )}
                </main>
            </div>
        </div>
    );
};
export default SettingsPage;
