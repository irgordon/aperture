import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import GalleryViewer from './GalleryViewer';

const ProofingGallery = ({ albumId }) => {
    const [images, setImages] = useState([]);
    useEffect(() => { apiFetch({path: `/aperture/v1/gallery/${albumId}`}).then(setImages); }, [albumId]);
    return <GalleryViewer images={images} mode="proof" albumId={albumId} />;
};
export default ProofingGallery;
