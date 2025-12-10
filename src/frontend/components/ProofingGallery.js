import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import GalleryViewer from './GalleryViewer';

const ProofingGallery = ({ albumId, hash }) => {
    const [images, setImages] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Pass hash to API for gate check
        apiFetch({path: `/aperture/v1/gallery/${albumId}?hash=${hash}`})
            .then(data => { setImages(data); setLoading(false); })
            .catch(err => { alert(err.message); setLoading(false); });
    }, [albumId, hash]);

    if (loading) return <div>Loading Gallery...</div>;

    return <GalleryViewer images={images} mode="proof" albumId={albumId} hash={hash} />;
};
export default ProofingGallery;
