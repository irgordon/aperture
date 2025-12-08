import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import './proofing-style.scss';

const ProofingGallery = ({ albumId }) => {
    const [images, setImages] = useState([]);
    const [selected, setSelected] = useState([]);

    useEffect(() => {
        apiFetch({path: `/aperture/v1/gallery/${albumId}`}).then(setImages);
    }, [albumId]);

    const toggle = (id) => {
        setSelected(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
    };

    const submit = () => {
        apiFetch({
            path: '/aperture/v1/gallery/submit', 
            method: 'POST', 
            data: { album_id: albumId, selected_ids: selected }
        }).then(() => alert('Selection Sent!'));
    };

    return (
        <div className="gallery-container">
            <div className="grid">
                {images.map(img => (
                    <div key={img.id} className={`item ${selected.includes(img.id)?'selected':''}`} onClick={()=>toggle(img.id)}>
                        <img src={img.public_url} />
                        <div className="meta">
                            <span>{img.serial_number}</span>
                            <input type="checkbox" checked={selected.includes(img.id)} readOnly />
                        </div>
                    </div>
                ))}
            </div>
            <div className="bar">
                <span>{selected.length} Selected</span>
                <button onClick={submit}>Send Selection</button>
            </div>
        </div>
    );
};
export default ProofingGallery;
