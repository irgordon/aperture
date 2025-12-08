import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';

const GalleryViewer = ({ images, mode, albumId }) => {
    const [selected, setSelected] = useState([]);
    const toggle = (id) => { if (selected.includes(id)) setSelected(selected.filter(x => x !== id)); else setSelected([...selected, id]); };
    const submitSelection = async () => { if(!confirm(`Submit ${selected.length} photos?`)) return; await apiFetch({ path: '/aperture/v1/gallery/submit', method: 'POST', data: { albumId, ids: selected } }); alert('Sent!'); };
    const preventRightClick = (e) => { if(mode === 'proof') { e.preventDefault(); alert('Protected.'); } };

    return (
        <div className="ap-gallery-container" onContextMenu={preventRightClick}>
            {mode === 'proof' && <div className="proofing-bar"><span>{selected.length} Selected</span><button onClick={submitSelection}>Send Selection</button></div>}
            <div className="gallery-grid" style={{display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(200px, 1fr))', gap:'15px'}}>
                {images.map(img => (
                    <div key={img.id} className={`gallery-item ${selected.includes(img.id) ? 'selected' : ''}`} onClick={() => mode === 'proof' && toggle(img.id)} style={{position:'relative'}}>
                        <img src={img.public_url} style={{width:'100%', display:'block'}} />
                        {mode === 'proof' && <div className="overlay" style={{position:'absolute', bottom:0, background:'rgba(0,0,0,0.5)', width:'100%', color:'white', padding:'5px'}}>{img.proof_id}</div>}
                    </div>
                ))}
            </div>
        </div>
    );
};
export default GalleryViewer;
