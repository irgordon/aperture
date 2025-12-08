import { createRoot } from 'react-dom/client';
import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import './portal-layout.scss';

const ContactForm = () => {
    const [data, setData] = useState({ firstName: '', lastName: '', email: '', message: '', phone: '' });
    const [status, setStatus] = useState('');

    const submit = (e) => {
        e.preventDefault();
        setStatus('sending');
        apiFetch({ path: '/aperture/v1/leads/public', method: 'POST', data })
            .then(() => setStatus('success'))
            .catch(() => setStatus('error'));
    };

    if (status === 'success') return <div className="success-msg">Message Sent! We will be in touch.</div>;

    return (
        <form className="ap-contact-form" onSubmit={submit}>
            <input required placeholder="First Name" onChange={e => setData({...data, firstName: e.target.value})} />
            <input required placeholder="Last Name" onChange={e => setData({...data, lastName: e.target.value})} />
            <input required type="email" placeholder="Email" onChange={e => setData({...data, email: e.target.value})} />
            <input placeholder="Phone" onChange={e => setData({...data, phone: e.target.value})} />
            <textarea required placeholder="Message" onChange={e => setData({...data, message: e.target.value})} />
            <button className="btn-primary" disabled={status === 'sending'}>Send Message</button>
        </form>
    );
};

const root = document.getElementById('aperture-pro-contact-form');
if (root) createRoot(root).render(<ContactForm />);
