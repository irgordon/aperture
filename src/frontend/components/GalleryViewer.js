import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';

const GalleryViewer = ({ images, mode, albumId, hash }) => {
    const [selected, setSelected] = useState([]);
    const [statuses, setStatuses] = useState({}); // { id: 'approved' | 'rejected' }

    useEffect(() => {
        // Init statuses from image data if available (API needs to send it)
        const initialStatuses = {};
        images.forEach(img => { if(img.status) initialStatuses[img.id] = img.status; });
        setStatuses(initialStatuses);
    }, [images]);

    const submitSelection = async () => {
        if(!confirm(`Submit ${selected.length} photos?`)) return;
        await apiFetch({ path: '/aperture/v1/gallery/submit', method: 'POST', data: { albumId, ids: selected, hash } });
        alert('Selection Sent!');
    };

    const updateStatus = async (id, status, e) => {
        e.stopPropagation();
        const newStatuses = { ...statuses, [id]: status };
        setStatuses(newStatuses);
        // Optimistic UI, then verify
        try {
            await apiFetch({ path: '/aperture/v1/gallery/update_status', method: 'POST', data: { id, status, hash } });
        } catch(err) {
            alert('Failed to update status');
        }
    };

    const preventRightClick = (e) => { if(mode === 'proof') { e.preventDefault(); alert('Protected.'); } };

    const approvedCount = Object.values(statuses).filter(s => s === 'approved').length;

    return (
        <div className="ap-gallery-container" onContextMenu={preventRightClick}>
            {mode === 'proof' && (
                <div className="proofing-bar sticky-bar">
                    <div className="stats">
                        <span>{approvedCount} Approved</span> / <span>{images.length} Total</span>
                    </div>
                    <div className="actions">
                        <button className="btn-primary" onClick={submitSelection}>Finalize Selection</button>
                    </div>
                </div>
            )}

            <div className="gallery-grid-responsive">
                {images.map(img => (
                    <div key={img.id} className={`gallery-item ${statuses[img.id] || img.status}`} style={{position:'relative'}}>
                        <img src={img.public_url} alt={img.file_name} />

                        {mode === 'proof' && (
                            <div className="img-actions">
                                <button
                                    className={`action-btn approve ${statuses[img.id] === 'approved' ? 'active' : ''}`}
                                    onClick={(e) => updateStatus(img.id, 'approved', e)}
                                >
                                    ✓
                                </button>
                                <button
                                    className={`action-btn reject ${statuses[img.id] === 'rejected' ? 'active' : ''}`}
                                    onClick={(e) => updateStatus(img.id, 'rejected', e)}
                                >
                                    ✕
                                </button>
                            </div>
                        )}
                        {mode === 'proof' && <div className="overlay-proof-id">{img.proof_id}</div>}
                    </div>
                ))}
            </div>
            <style>{`
                .sticky-bar { position: sticky; top: 0; background: white; padding: 15px; border-bottom: 1px solid #eee; z-index: 100; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                .gallery-grid-responsive { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; padding: 20px 0; }
                .gallery-item { position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.2s; }
                .gallery-item:hover { transform: translateY(-2px); }
                .gallery-item img { width: 100%; height: auto; display: block; }
                .gallery-item.rejected img { opacity: 0.5; filter: grayscale(100%); }
                .gallery-item.approved { border: 4px solid #14b8a6; }
                .img-actions { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; gap: 10px; background: rgba(0,0,0,0.7); padding: 5px; border-radius: 20px; }
                .action-btn { background: transparent; border: 1px solid white; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; }
                .action-btn.approve:hover, .action-btn.approve.active { background: #14b8a6; border-color: #14b8a6; }
                .action-btn.reject:hover, .action-btn.reject.active { background: #f43f5e; border-color: #f43f5e; }
                .overlay-proof-id { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.5); color: white; padding: 2px 6px; font-size: 10px; border-radius: 4px; }
            `}</style>
        </div>
    );
};
export default GalleryViewer;
