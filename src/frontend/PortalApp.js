import { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import apiFetch from '@wordpress/api-fetch';
import './portal.scss';

const PortalApp = () => {
    const [data, setData] = useState(null);
    const [view, setView] = useState('home'); // home, invoice, contract, gallery

    // Get the hash from URL (e.g. /portal/xyz123)
    const hash = window.location.pathname.split('/').pop();

    useEffect(() => {
        apiFetch({ path: `/aperture/v1/portal/${hash}` }).then(setData);
    }, []);

    if (!data) return <div className="ap-loading">Loading your portal...</div>;

    const { client, project, progress } = data;

    return (
        <div className="ap-portal">
            <aside className="portal-nav">
                <div className="brand">AperturePro</div>
                <div className="client-info">
                    <div className="avatar">{client.first_name[0]}</div>
                    <span>Welcome, {client.first_name}</span>
                </div>
                <nav>
                    <button className={view==='home'?'active':''} onClick={()=>setView('home')}>Dashboard</button>
                    <button className={view==='invoice'?'active':''} onClick={()=>setView('invoice')}>Invoices</button>
                    <button className={view==='contract'?'active':''} onClick={()=>setView('contract')}>Contracts</button>
                    <button className={view==='gallery'?'active':''} onClick={()=>setView('gallery')}>Gallery</button>
                </nav>
            </aside>

            <main className="portal-main">
                {view === 'home' && (
                    <div className="dashboard-view">
                        <header>
                            <h1>{project.title}</h1>
                            <span className="date">{project.event_date}</span>
                        </header>

                        {/* Progress Tracker */}
                        <div className="progress-bar-container">
                            <div className="steps">
                                {['Inquiry', 'Proposal', 'Contract', 'Booked', 'Gallery'].map((step, i) => (
                                    <div key={step} className={`step ${i <= progress ? 'completed' : ''}`}>
                                        <div className="circle">{i <= progress ? '✓' : i+1}</div>
                                        <span>{step}</span>
                                    </div>
                                ))}
                            </div>
                            <div className="bar-bg"><div className="bar-fill" style={{width: `${progress * 25}%`}}></div></div>
                        </div>

                        <div className="cards-grid">
                            <div className="card action-required">
                                <h3>Action Required</h3>
                                <p>You have 1 unpaid invoice.</p>
                                <button onClick={()=>setView('invoice')}>Pay Now</button>
                            </div>
                            <div className="card">
                                <h3>Your Team</h3>
                                <p>Photographer: Ian Gordon</p>
                                <a href="mailto:hello@iangordon.app">Contact Us</a>
                            </div>
                        </div>
                    </div>
                )}
                
                {/* Other views (Invoice/Contract) would use the components we built previously */}
                {view === 'invoice' && <div><InvoiceComponent data={data.invoices} /></div>}
            </main>
        </div>
    );
};

const root = document.getElementById('aperture-client-portal');
if(root) createRoot(root).render(<PortalApp />);
