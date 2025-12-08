import { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import apiFetch from '@wordpress/api-fetch';
import { loadStripe } from '@stripe/stripe-js';
import { Elements } from '@stripe/react-stripe-js';
import PaymentForm from './components/PaymentForm';
import './portal-style.scss';

// REPLACE WITH YOUR PUBLISHABLE KEY IN PROD
const stripePromise = loadStripe('pk_test_YOUR_KEY');

const PortalApp = () => {
    const [data, setData] = useState(null);
    const params = new URLSearchParams(window.location.search);
    const id = params.get('invoice_id');

    useEffect(() => {
        if(id) apiFetch({path: `/aperture/v1/invoices/public/${id}`}).then(setData);
    }, [id]);

    if(!data) return <div>Loading...</div>;

    return (
        <div className="portal-container">
            <header>
                {data.branding.logo_url && <img src={data.branding.logo_url} alt="Logo"/>}
                <h1>{data.branding.company_name}</h1>
            </header>
            <div className="invoice-grid">
                <div className="details">
                    <h2>Invoice #{data.invoice.invoice_number}</h2>
                    <p>Amount: ${data.invoice.amount}</p>
                    <p>Status: {data.invoice.status}</p>
                </div>
                <div className="payment">
                    {data.invoice.status !== 'paid' ? (
                        <Elements stripe={stripePromise}>
                            <PaymentForm clientSecret={data.client_secret} />
                        </Elements>
                    ) : (
                        <div className="success">Paid in Full</div>
                    )}
                </div>
            </div>
        </div>
    );
};

const root = document.getElementById('aperture-client-portal');
if(root) createRoot(root).render(<PortalApp />);
