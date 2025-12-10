import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const DeliveryView = ({ hash, branding }) => {
    const [info, setInfo] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        apiFetch({ path: `/aperture/v1/gallery/delivery/${hash}` })
            .then(data => { setInfo(data); setLoading(false); })
            .catch(err => { alert(err.message); setLoading(false); });
    }, [hash]);

    if (loading) return <div>Loading delivery info...</div>;
    if (!info) return <div>Delivery information not available.</div>;

    const isExpired = new Date(info.expiry_date) < new Date();

    return (
        <div className="delivery-container" style={{maxWidth: '800px', margin: '40px auto', padding: '20px', textAlign: 'center'}}>
            <div className="brand-header" style={{marginBottom: '40px'}}>
                {branding.logo_url && <img src={branding.logo_url} alt="Logo" style={{height: '60px'}} />}
            </div>

            <h1>Your Gallery is Ready</h1>

            {info.delivery_notes && (
                <div className="delivery-notes" style={{background: '#f9fafb', padding: '20px', borderRadius: '8px', margin: '20px 0', textAlign: 'left'}}>
                    <h3>A Note from your Photographer:</h3>
                    <p>{info.delivery_notes}</p>
                </div>
            )}

            {isExpired ? (
                <div className="alert-error">This link has expired. Please contact us to restore access.</div>
            ) : (
                <div className="download-section">
                    {info.is_zip_ready ? (
                        <a href={info.zip_url} className="btn-primary" style={{display: 'inline-block', padding: '15px 30px', background: branding.primary_color || '#14b8a6', color: 'white', textDecoration: 'none', borderRadius: '8px', fontSize: '18px', fontWeight: 'bold'}}>
                            Download Full Gallery (ZIP)
                        </a>
                    ) : (
                        <div className="processing">
                            <p>We are preparing your ZIP file. Please check back shortly or wait for the email.</p>
                        </div>
                    )}

                    <p style={{marginTop: '20px', fontSize: '12px', color: '#666'}}>
                        Link expires on: {new Date(info.expiry_date).toLocaleDateString()}
                    </p>
                </div>
            )}
        </div>
    );
};
export default DeliveryView;
