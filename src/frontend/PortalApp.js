import { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import apiFetch from '@wordpress/api-fetch';
import './portal.scss';
import ProofingGallery from './components/ProofingGallery';
import DeliveryView from './components/DeliveryView';
// Import Invoice/Contract components if they exist (assuming simple placeholders for now to focus on Gallery)

const PortalApp = () => {
    const [data, setData] = useState(null);
    const [view, setView] = useState('home');
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Parse hash from URL query param ?hash=... or path
    const urlParams = new URLSearchParams(window.location.search);
    const hash = urlParams.get('hash') || window.location.pathname.split('/').pop();

    useEffect(() => {
        if(!hash) { setError('No project hash found.'); setLoading(false); return; }

        apiFetch({ path: `/aperture/v1/portal/project/${hash}` })
            .then(res => { setData(res); setLoading(false); })
            .catch(err => { setError(err.message); setLoading(false); });
    }, [hash]);

    if (loading) return <div className="ap-loading">Loading your portal...</div>;
    if (error) return <div className="ap-error">{error}</div>;

    const { lead, invoices, contracts, branding } = data;
    // We assume 'progress' logic (array of steps) was added to get_project_data response in API
    const progressSteps = lead.progress || [];

    return (
        <div className="ap-portal">
             <style>{`:root { --teal-primary: ${branding.primary_color || '#14b8a6'}; }`}</style>

            <aside className="portal-nav">
                <div className="brand-logo">
                    {branding.logo_url ? <img src={branding.logo_url} alt="Logo" /> : 'AperturePro'}
                </div>

                <nav>
                    <button className={view==='home'?'active':''} onClick={()=>setView('home')}>Dashboard</button>
                    <button className={view==='invoice'?'active':''} onClick={()=>setView('invoice')}>Invoices ({invoices.length})</button>
                    <button className={view==='contract'?'active':''} onClick={()=>setView('contract')}>Contracts ({contracts.length})</button>
                    <button className={view==='gallery'?'active':''} onClick={()=>setView('gallery')}>Gallery</button>
                    <button className={view==='delivery'?'active':''} onClick={()=>setView('delivery')}>Delivery</button>
                </nav>
            </aside>

            <main className="portal-main">
                {view === 'home' && (
                    <div className="dashboard-view">
                        <header>
                            <h1>{lead.title}</h1>
                            <span className="date">{lead.event_date}</span>
                        </header>

                        {/* Progress Tracker */}
                        <div className="progress-bar-container">
                            <div className="steps">
                                {progressSteps.map((step, i) => (
                                    <div key={i} className={`step ${step.status}`}>
                                        <div className="circle">{step.status==='completed' ? '✓' : i+1}</div>
                                        <span>{step.label}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="cards-grid">
                           {/* Widgets */}
                           <div className="card">
                               <h3>Welcome!</h3>
                               <p>Track your project status here.</p>
                           </div>
                        </div>
                    </div>
                )}
                
                {view === 'gallery' && (
                    <div className="gallery-view">
                        <h2>Proofing Gallery</h2>
                        {/* We assume lead has an ID we can map to album ID, or API handles it.
                            Using lead.id as albumId for now based on previous context */}
                        <ProofingGallery albumId={lead.id} hash={hash} />
                    </div>
                )}

                {view === 'delivery' && (
                    <DeliveryView hash={hash} branding={branding} />
                )}

                {view === 'invoice' && (
                    <div className="view-container">
                        <h2>Invoices</h2>
                        <ul className="invoice-list">
                            {invoices.map(inv => (
                                <li key={inv.id} className="item-row">
                                    <span>#{inv.invoice_number}</span>
                                    <span>${inv.amount}</span>
                                    <span className={`status ${inv.status}`}>{inv.status}</span>
                                    {inv.status !== 'paid' && <a href={`/invoices/public/${inv.id}?hash=${hash}`} target="_blank" className="btn-small">Pay</a>}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {view === 'contract' && (
                     <div className="view-container">
                        <h2>Contracts</h2>
                        <ul>
                             {contracts.map(c => (
                                <li key={c.id} className="item-row">
                                    <span>Contract #{c.id}</span>
                                    <span className={`status ${c.status}`}>{c.status}</span>
                                    {/* Link to sign would go here */}
                                </li>
                             ))}
                        </ul>
                     </div>
                )}
            </main>
        </div>
    );
};

const root = document.getElementById('aperture-client-portal');
if(root) createRoot(root).render(<PortalApp />);
