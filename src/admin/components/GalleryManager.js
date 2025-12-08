import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import GalleryUploader from './GalleryUploader'; // Ensure you have this file from previous steps!

const GalleryManager = () => {
    const [view, setView] = useState('list'); // 'list' or 'upload'
    const [albums, setAlbums] = useState([]); // In a real app, fetch these from DB
    const [activeAlbum, setActiveAlbum] = useState(null);

    // Mock creating an album for now (since we focus on upload logic)
    const createAlbum = () => {
        const id = Date.now();
        setAlbums([...albums, { id, name: `Album #${id}` }]);
        setActiveAlbum(id);
        setView('upload');
    };

    return (
        <div className="ap-gallery-manager">
            <div className="header" style={{display:'flex', justifyContent:'space-between', marginBottom:'20px'}}>
                <h2>Client Galleries</h2>
                {view === 'list' && (
                    <button className="btn-primary" onClick={createAlbum}>+ New Album</button>
                )}
                {view === 'upload' && (
                    <button className="btn-secondary" onClick={() => setView('list')}>Back to List</button>
                )}
            </div>

            {view === 'list' ? (
                <div className="album-grid" style={{display:'grid', gap:'20px', gridTemplateColumns:'repeat(auto-fill, minmax(200px, 1fr))'}}>
                    {albums.length === 0 && <p>No albums yet.</p>}
                    {albums.map(album => (
                        <div key={album.id} className="ap-card" onClick={() => { setActiveAlbum(album.id); setView('upload'); }}>
                            <h4>{album.name}</h4>
                            <p style={{fontSize:'12px', color:'#666'}}>ID: {album.id}</p>
                        </div>
                    ))}
                </div>
            ) : (
                <div className="upload-view">
                    <h3>Managing: Album #{activeAlbum}</h3>
                    <p style={{marginBottom:'20px'}}>
                        Shortcode: <code>[aperture_proofing album_id="{activeAlbum}"]</code>
                    </p>
                    {/* Make sure src/admin/components/GalleryUploader.js exists! */}
                    <GalleryUploader albumId={activeAlbum} />
                </div>
            )}
        </div>
    );
};
export default GalleryManager;
