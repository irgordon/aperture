import { useState, useEffect, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import apiFetch from '@wordpress/api-fetch';
import { loadStripe } from '@stripe/stripe-js';
import { Elements } from '@stripe/react-stripe-js';
import SignatureCanvas from 'react-signature-canvas';
import PaymentForm from './components/PaymentForm';
import ProofingGallery from './components/ProofingGallery';
import './portal-layout.scss';

const stripePromise = loadStripe(window.apertureProSettings?.stripe_key || '');

const UnifiedPortal = () => {
    const pathSegments = window.location.pathname.split('/');
    const portalIndex = pathSegments.indexOf('portal');
    // NOTE: This assumes URL structure /portal/{hash}
    // If testing on localhost/?page_id=X&hash=Y, use URLSearchParams instead
    const urlParams = new URLSearchParams(window.location.search);
    const projectHash = urlParams.get('hash') || ((portalIndex !== -1 && pathSegments[portalIndex + 1]) ? pathSegments[portalIndex + 1] : null);
    
    const [data, setData] = useState(null);
    const [activeTab, setActiveTab] = useState('info');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!projectHash) { setLoading(false); return; }
        apiFetch({ path: `/aperture/v1/portal/project/${projectHash}` })
            .then(res => { setData(res); setLoading(false); })
            .catch(() => setLoading(false));
    }, [projectHash]);

    if (loading) return <div className="ap-loading">Loading...</div>;
    if (!data) return <div className="ap-login-prompt">Access Denied. Project hash required.</div>;

    const { lead, invoice, contract, branding, user_role } = data;
    const isAdmin = user_role === 'admin';

    const InfoTab = () => (
        <div className="tab-content fade-in">
            <h3>Customer Information</h3>
            <div className="info-card">
                <div className="row"><label>Name:</label><span>{lead.first_name} {lead.last_name}</span></div>
                <div className="row"><label>Email:</label><span>{lead.email}</span></div>
                <div className="row"><label>Address:</label><span>{lead.address}</span></div>
            </div>
        </div>
    );

    const ContractTab = () => {
        const sigPad = useRef({});
        const [signing, setSigning] = useState(false);

        if (contract?.status === 'signed') {
            return (
                <div className="tab-content fade-in">
                    <div className="success-box" style={{textAlign:'center', padding:'40px'}}>
                        <h3 style={{color:'#10B981'}}>✓ Contract Secured</h3>
                        {contract.pdf_path && <a href={contract.pdf_path} target="_blank" className="btn-primary" download>Download PDF</a>}
                    </div>
                </div>
            );
        }

        const handleSign = async () => {
            if (sigPad.current.isEmpty()) return alert("Sign in the box.");
            const name = prompt("Type full name to confirm:");
            if (!name) return;
            setSigning(true);
            try {
                await apiFetch({
                    path: '/aperture/v1/portal/contract/sign-internal',
                    method: 'POST',
                    data: { contract_id: contract.id, signature_image: sigPad.current.getTrimmedCanvas().toDataURL('image/png'), signer_name: name }
                });
                window.location.reload();
            } catch (e) { alert(e.message); setSigning(false); }
        };

        return (
            <div className="tab-content fade-in">
                <div className="contract-paper">
                    <div className="contract-header"><h2>Agreement</h2><span className="status-badge draft">Pending</span></div>
                    <div className="contract-body" dangerouslySetInnerHTML={{ __html: contract?.content || '<p>No contract found.</p>' }} />
                    <div className="signature-area">
                        <p>Sign Below:</p>
                        <div style={{border:'2px dashed #ccc'}}><SignatureCanvas penColor='black' canvasProps={{width: 500, height: 200, className: 'sigCanvas'}} ref={sigPad} /></div>
                        <div style={{marginTop:'10px'}}><button className="btn-secondary" onClick={()=>sigPad.current.clear()}>Clear</button> <button className="btn-primary" onClick={handleSign} disabled={signing}>{signing?'Signing...':'Adopt & Sign'}</button></div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="unified-portal">
            <header className="portal-topbar">
                <div className="brand">{branding.logo_url && <img src={branding.logo_url} />} <span>{lead.first_name}'s Portal</span></div>
                <div className="user-meta">{isAdmin ? 'Admin View' : 'Client View'}</div>
            </header>
            <div className="portal-body">
                <aside className="portal-sidebar">
                    <nav>
                        <button className={activeTab==='info'?'active':''} onClick={()=>setActiveTab('info')}>Info</button>
                        <button className={activeTab==='invoice'?'active':''} onClick={()=>setActiveTab('invoice')}>Invoice</button>
                        <button className={activeTab==='contract'?'active':''} onClick={()=>setActiveTab('contract')}>Contract</button>
                        <button className={activeTab==='gallery'?'active':''} onClick={()=>setActiveTab('gallery')}>Gallery</button>
                    </nav>
                </aside>
                <main className="portal-content">
                    {activeTab === 'info' && <InfoTab />}
                    {activeTab === 'invoice' && invoice && <Elements stripe={stripePromise}><PaymentForm clientSecret={data.client_secret} /></Elements>}
                    {activeTab === 'contract' && <ContractTab />}
                    {activeTab === 'gallery' && <ProofingGallery albumId={lead.id} />}
                </main>
            </div>
        </div>
    );
};

const root = document.getElementById('aperture-client-portal');
if(root) createRoot(root).render(<UnifiedPortal />);
