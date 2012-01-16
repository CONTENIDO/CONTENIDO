package org.contenido;

import java.applet.Applet;
import java.awt.Color;
import java.awt.Component;
import java.awt.Panel;
import java.io.File;
import java.io.UnsupportedEncodingException;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;

/**
 * A collection of uploads, which handles the size of them and keeps track of the status of the uploads. If all of them are successfully finished, it tells the browser to show the overview of uploaded files in the CONTENIDO backend.
 * 
 * @author Mischa Holz
 *
 * @version 1.1, 01/13/2012
 * 
 * @since 1.0
 */
public class UploadStatusShelf extends Panel {
	private static final long serialVersionUID = 1L;
	
	private ArrayList<UploadStatusBar> uploads;
	private String uid;
	private String host;
	private Applet instance;
	private String uploadPath;
	
	/**
	 * Initializes the component and saves the necessary variables to the instance.
	 * @param apl the applet instance to get the applet context if we want to load the new page
	 * @param auid the CONTENIDO uid, which gets sent to the webserver for authentication
	 * @param ahost the path to the main.php document ending with /
	 * @param auploadPath the relative path to upload to
	 */
	public UploadStatusShelf(Applet apl, String auid, String ahost, String auploadPath) {
		uploads = new ArrayList<UploadStatusBar>();
		
		this.setBounds(DropboxMain.shelfX, DropboxMain.shelfY, DropboxMain.shelfWidth, DropboxMain.appletHeight - DropboxMain.dropAreaHeight);
		this.setBackground(Color.white);
		
		uid = auid;
		host = ahost;
		instance = apl;
		uploadPath = auploadPath;
	}
	
	/**
	 * Adds a new upload to the shelf by creating a new <code>UploadStatusBar</code> and stores it for later use.
	 * @param afile the file to upload
	 */
	public void addNewUpload(File afile) {
		UploadStatusBar usb = new UploadStatusBar(this, host, afile, uid, uploadPath);
		
		this.addUploadStatusBar(usb);
		
		this.repaint();
	}
	
	/**
	 * Updates the size of all of the sub components and the window displaying the UploadStatusShelf. It calls <code>UploadStatusBar.updateSize()</code> for all uploads.
	 */
	public void updateSize() {
		setSize(DropboxMain.shelfWidth - 1, uploads.size() * DropboxMain.barHeight + 27);
		
		Component c = getParent();
		if(c != null) {
			while(c.getParent() != null) {
				c.setSize(getSize());
				c = c.getParent();
			}
			c.setSize(getSize());
		}
		
		for(int i = 0; i < uploads.size(); i++) {
			uploads.get(i).updateSize();
		}
	}
	
	/**
	 * Returns the current number of uploads running.
	 * @return the number of uploads
	 */
	public int getSizeOfShelf() {
		return uploads.size();
	}
	
	/**
	 * Returns the position of the <code>UploadStatusBar</code> <code>usb</code> within the applet.
	 * @param usb the UploadStatusBar which position should be determined
	 * @return the position in the uploads <code>ArrayList</code>
	 */
	public int getPosition(UploadStatusBar usb) {
		for(int i = 0; i < uploads.size(); i++) {
			if(uploads.get(i).equals(usb)) {
				return i;
			}
		}
		return -1;
	}
	
	/**
	 * Removes the <code>UploadStatusBar</code> <code>usb</code> from the array and all of its components. If all uploads are successfully finished, it tells the browser to show the overview of all uploaded files in the CONTENIDO backend.
	 * @param usb the <code>UploadStatusBar</code> which should be removed.
	 * @param aresponse the HTML response of the server after the upload <code>usb</code> was finished.
	 */
	public void remove(UploadStatusBar usb, String aresponse) {
		uploads.remove(usb);
		
		this.remove((Component) usb);
		if(uploads.size() == 0) {
			Component c = getParent();
			while(c.getParent() != null) {
				c = c.getParent();
			}
			c.setVisible(false);
			
			try {
				//instance.getAppletContext().showDocument(new URL(host + "main.php?area=upl&frame=4&path=" + uploadPath + "&contenido=" + uid), "right_bottom");
				instance.getAppletContext().showDocument(new URL(host + "main.php?area=upl&frame=4&path="+ URLEncoder.encode(uploadPath, "UTF-8") + "&appendparameters=&contenido=" + uid), "right_bottom");
				System.exit(0);
			} catch (MalformedURLException e) {
				e.printStackTrace();
			} catch (UnsupportedEncodingException e) {
				e.printStackTrace();
			}
		}
	}
	
	/**
	 * Adds <code>usb</code> to the components of this and to the uploads <code>ArrayList</code>.
	 * @param usb the <code>UploadStatusBar</code> which will be added
	 */
	public void addUploadStatusBar(UploadStatusBar usb) {
		uploads.add(usb);
		this.add(usb);
	}
}
